<?php

namespace Coderstm\Commands;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePagesJson extends Command
{
    protected $signature = 'pages:make-json {--path= : Optional path to source directory}';

    protected $description = 'Convert Blade templates to GrapesJS JSON format';

    protected $pages = [];

    public function handle()
    {
        $sourceDir = $this->option('path') ?: config('coderstm.editor.pages_path');
        $this->info('Source: '.$sourceDir);
        if (! File::isDirectory($sourceDir)) {
            $this->error("Directory not found: {$sourceDir}");

            return Command::FAILURE;
        }
        $this->loadExistingPages($sourceDir);
        $files = $this->getAllBladeFiles($sourceDir);
        $items = [];
        foreach ($files as $file) {
            $items[] = $this->parseFileMeta($file, $sourceDir);
        }
        foreach ($items as $item) {
            $this->processItem($item, $sourceDir);
        }
        $this->info('Pages JSON generated successfully.');

        return Command::SUCCESS;
    }

    protected function getAllBladeFiles($dir)
    {
        $files = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $files = array_merge($files, $this->getAllBladeFiles($path));
            } elseif (str_ends_with($path, '.blade.php')) {
                if (in_array($item, ['blog.blade.php', 'blogs.blade.php'])) {
                    continue;
                }
                $files[] = $path;
            }
        }

        return $files;
    }

    protected function loadExistingPages($sourceDir)
    {
        $indexFile = "{$sourceDir}/index.json";
        if (File::exists($indexFile)) {
            $this->pages = json_decode(File::get($indexFile), true);
        }
    }

    protected function getAllJsonFiles($dir)
    {
        $files = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $files = array_merge($files, $this->getAllJsonFiles($path));
            } elseif (str_ends_with($path, '.json')) {
                $files[] = $path;
            }
        }

        return $files;
    }

    protected function parseFileMeta($file, $sourceDir)
    {
        $content = File::get($file);
        $filename = basename($file, '.blade.php');
        $slug = $filename;
        $parent = null;
        if (preg_match("/'parent'\\s*=>\\s*'((?:[^'\\\\]|\\\\.)*)'/", $content, $m)) {
            $parent = str_replace("\\'", "'", $m[1]);
        }
        $title = str($filename)->title();
        if (preg_match("/'title'\\s*=>\\s*'((?:[^'\\\\]|\\\\.)*)'/", $content, $m)) {
            $title = trim(explode('|', str_replace("\\'", "'", $m[1]))[0]);
        }
        $metaTitle = null;
        if (preg_match("/'meta_title'\\s*=>\\s*'((?:[^'\\\\]|\\\\.)*)'/", $content, $m)) {
            $metaTitle = trim(explode('|', str_replace("\\'", "'", $m[1]))[0]);
        }
        $metaDescription = '';
        if (preg_match("/'meta_description'\\s*=>\\s*'((?:[^'\\\\]|\\\\.)*)'/", $content, $m)) {
            $metaDescription = str_replace("\\'", "'", $m[1]);
        }
        $metaKeywords = '';
        if (preg_match("/'meta_keywords'\\s*=>\\s*'((?:[^'\\\\]|\\\\.)*)'/", $content, $m)) {
            $metaKeywords = str_replace("\\'", "'", $m[1]);
        }

        return ['file' => $file, 'filename' => $filename, 'slug' => $slug, 'parent' => $parent, 'title' => $title, 'metaTitle' => $metaTitle, 'metaDescription' => $metaDescription, 'metaKeywords' => $metaKeywords, 'content' => $content];
    }

    protected function processItem($item, $sourceDir)
    {
        if (! preg_match("/@section\\('content'\\)([\\s\\S]*?)@endsection/", $item['content'], $match)) {
            $this->warn("Skipping {$item['file']}: No @section('content') found.");

            return;
        }
        $htmlContent = $match[1];
        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTML('<?xml encoding="utf-8" ?><body>'.$htmlContent.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $rootComponents = [];
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            foreach ($body->childNodes as $node) {
                $comp = $this->processNode($node);
                if ($comp) {
                    $rootComponents[] = $comp;
                }
            }
        }
        $cssContent = '';
        if (preg_match("/@section\\('style'\\)([\\s\\S]*?)@endsection/", $item['content'], $match)) {
            $cssContent = trim(preg_replace('/<\\/?style[^>]*>/', '', $match[1]));
        }
        $currentPage = $this->pages[$item['slug']] ?? null;
        $grapesJson = ['id' => $currentPage['id'] ?? null, 'title' => $item['title'], 'slug' => $item['slug'], 'parent' => $item['parent'] ?? null, 'meta_title' => $item['metaTitle'], 'meta_keywords' => $item['metaKeywords'], 'meta_description' => $item['metaDescription'], 'data' => ['pages' => [['id' => $this->generateId(), 'type' => 'main', 'frames' => [['id' => $this->generateId(), 'component' => ['type' => 'wrapper', 'stylable' => ['background', 'background-color', 'background-image', 'background-repeat', 'background-attachment', 'background-position', 'background-size'], 'components' => $rootComponents, 'head' => ['type' => 'head'], 'docEl' => ['tagName' => 'html']]]]]]]];
        if ($cssContent) {
            $grapesJson['data']['styles'] = $this->parseCssToGrapes($cssContent);
        }
        if (empty($item['title']) && $currentPage && isset($currentPage['title'])) {
            $grapesJson['title'] = $currentPage['title'];
        }
        if (empty($item['parent']) && $currentPage && isset($currentPage['parent'])) {
            $grapesJson['parent'] = $currentPage['parent'];
        }
        if (empty($item['meta_title']) && $currentPage && isset($currentPage['meta_title'])) {
            $grapesJson['meta_title'] = $currentPage['meta_title'];
        }
        if (empty($item['meta_keywords']) && $currentPage && isset($currentPage['meta_keywords'])) {
            $grapesJson['meta_keywords'] = $currentPage['meta_keywords'];
        }
        if (empty($item['meta_description']) && $currentPage && isset($currentPage['meta_description'])) {
            $grapesJson['meta_description'] = $currentPage['meta_description'];
        }
        $outFile = dirname($item['file']).'/'.$item['filename'].'.json';
        File::put($outFile, json_encode($grapesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Converted '.File::basename($item['file']).' -> '.File::basename($outFile));
    }

    protected function processNode(DOMNode $node)
    {
        if ($node instanceof DOMText) {
            $content = trim($node->textContent);
            if (strlen($content) > 0) {
                if (preg_match('/^\\[(\\w+)([\\s\\S]*?)\\]$/', $content, $matches)) {
                    $name = $matches[1];
                    $rawAttrs = $matches[2];
                    $component = ['type' => $name, 'classes' => ['shortcode'], 'content' => $content, 'shortcode' => $content, 'attributes' => ['id' => $this->generateId(4)], 'components' => [['type' => 'textnode', 'content' => $content]]];
                    if (preg_match_all('/(\\w+)="([^"]*)"/', $rawAttrs, $attrMatches, PREG_SET_ORDER)) {
                        foreach ($attrMatches as $am) {
                            $component[$am[1]] = $am[2];
                        }
                    }

                    return $component;
                }

                return ['type' => 'textnode', 'content' => $node->textContent];
            }

            return null;
        }
        if ($node instanceof DOMElement) {
            $attributes = [];
            $classes = [];
            $idAttrs = [];
            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attr) {
                    if ($attr->nodeName === 'class') {
                        $classes = preg_split('/\\s+/', $attr->nodeValue, -1, PREG_SPLIT_NO_EMPTY);
                    } elseif ($attr->nodeName === 'id') {
                        $idAttrs['id'] = $attr->nodeValue;
                    } else {
                        $attributes[$attr->nodeName] = $attr->nodeValue;
                    }
                }
            }
            $component = ['tagName' => $node->tagName, 'classes' => $classes, 'attributes' => array_merge($attributes, $idAttrs), 'components' => []];
            if ($node->tagName === 'img') {
                $component['type'] = 'image';
            } elseif ($node->tagName === 'a') {
                $component['type'] = 'link';
            } elseif (in_array($node->tagName, ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'strong', 'em', 'b', 'i', 'small', 'label', 'li'])) {
                $component['type'] = 'text';
            }
            if ($node->hasChildNodes()) {
                foreach ($node->childNodes as $child) {
                    $childComponent = $this->processNode($child);
                    if ($childComponent) {
                        $component['components'][] = $childComponent;
                    }
                }
            }

            return $component;
        }

        return null;
    }

    protected function parseCssToGrapes($css)
    {
        $styles = [];
        $css = preg_replace('/\\/\\*[\\s\\S]*?\\*\\//', '', $css);
        if (preg_match_all('/([^{]+)\\{([^}]+)\\}/', $css, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $selector = trim($match[1]);
                $body = trim($match[2]);
                $style = [];
                $decls = explode(';', $body);
                foreach ($decls as $decl) {
                    $decl = trim($decl);
                    if (empty($decl)) {
                        continue;
                    }
                    $parts = explode(':', $decl, 2);
                    if (count($parts) === 2) {
                        $prop = trim($parts[0]);
                        $val = trim($parts[1]);
                        $style[$prop] = $val;
                        if ($prop === 'background-image' && str_contains($val, 'gradient')) {
                            $style['__background-type'] = 'grad';
                        }
                    }
                }
                $selectors = array_map('trim', explode(',', $selector));
                $styles[] = ['selectors' => $selectors, 'selectorsAdd' => $selectors[0], 'style' => $style];
            }
        }

        return $styles;
    }

    protected function generateId($length = 16)
    {
        return Str::random($length);
    }
}
