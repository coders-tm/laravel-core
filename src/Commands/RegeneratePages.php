<?php

namespace Coderstm\Commands;

use Coderstm\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RegeneratePages extends Command
{
    protected $signature = 'pages:regenerate {--fresh : Force delete all pages before regeneration}';

    protected $description = 'Regenerate the centralized pages registry file from database';

    public function handle()
    {
        $this->info('Seeding pages from JSON...');
        $pagesPath = config('coderstm.editor.pages_path');
        if (! File::isDirectory($pagesPath)) {
            $this->error("Directory not found: {$pagesPath}");

            return Command::FAILURE;
        }
        $this->migratePages($pagesPath);
        $files = glob($pagesPath.'/*.json');
        if ($this->option('fresh')) {
            $this->warn('Force deleting all pages...');
            Page::withTrashed()->forceDelete();
        }
        foreach ($files as $file) {
            $filename = basename($file);
            if ($filename === 'index.json') {
                continue;
            }
            $page = json_decode(replace_short_code(file_get_contents($file)), true);
            unset($page['id'], $page['view'], $page['data']);
            $this->info("Processing {$filename}...");
            Page::withTrashed()->updateOrCreate(['slug' => $page['slug']], $page);
        }
        $this->info('Regenerating page registry...');
        $registryPath = config('coderstm.editor.registry_path');
        $registry = [];
        $pages = Page::where('is_active', true)->get();
        if ($pages->isEmpty()) {
            $this->warn('No active pages found in database.');
            if (File::exists($registryPath)) {
                File::delete($registryPath);
                $this->info('Registry file deleted as no pages exist.');
            }

            return Command::SUCCESS;
        }
        foreach ($pages as $page) {
            $registry[$page->slug] = ['id' => $page->id, 'slug' => $page->slug, 'parent' => $page->parent, 'title' => $page->title, 'meta_title' => $page->meta_title, 'meta_keywords' => $page->meta_keywords, 'meta_description' => $page->meta_description, 'options' => $page->options];
        }
        File::ensureDirectoryExists(dirname($registryPath));
        File::put($registryPath, json_encode($registry, JSON_PRETTY_PRINT));
        $this->info("Successfully regenerated registry with {$pages->count()} pages.");
        $this->info("Registry file: {$registryPath}");

        return Command::SUCCESS;
    }

    protected function migratePages($pagesPath)
    {
        $this->info('Starting page migration process...');
        $pages = Page::all();
        foreach ($pages as $page) {
            $this->info("Processing page: {$page->slug} (ID: {$page->id})");
            $idPath = $pagesPath."/{$page->id}.blade.php";
            $slugPath = $pagesPath."/{$page->slug}.blade.php";
            if (file_exists($idPath) && ! file_exists($slugPath)) {
                rename($idPath, $slugPath);
                $this->info("Renamed {$page->id}.blade.php to {$page->slug}.blade.php");
                $content = file_get_contents($slugPath);
                $metadata = ['title' => $page->title, 'slug' => $page->slug, 'parent' => $page->parent, 'meta_title' => $page->meta_title, 'meta_description' => $page->meta_description, 'meta_keywords' => $page->meta_keywords, 'options' => $page->options ?? []];
                $metadata = array_filter($metadata, function ($value) {
                    return ! is_null($value);
                });
                $export = var_export($metadata, true);
                $replacement = "@extends('layouts.page', {$export})";
                $content = preg_replace("/@extends\\(\\s*['\"]layouts\\.page['\"]\\s*\\)/", $replacement, $content);
                file_put_contents($slugPath, $content);
                $this->info("Updated metadata in {$page->slug}.blade.php");
            } else {
                $this->info("{$page->slug}.blade.php already migrated, skipping.");

                continue;
            }
            if (empty($page->data)) {
                $this->info("Page (ID: {$page->id}) doesn't have JSON data, skipping.");

                continue;
            }
            $page->publishJson();
            if (app()->isProduction()) {
                $page->updateQuietly(['data' => null]);
            }
            if (in_array($page->template, ['blogs', 'blog'])) {
                $page->forceDelete();
                $jsonPath = "{$pagesPath}/{$page->slug}.json";
                if (file_exists($jsonPath)) {
                    unlink($jsonPath);
                }
            }
        }
        $this->info('All pages have been migrated successfully.');
    }
}
