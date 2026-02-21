<?php

namespace Coderstm\Services;

use Illuminate\Support\Str;

class NotificationTemplateRenderer
{
    public function render(string $template, array $data = []): string
    {
        try {
            $compiler = app('blade.compiler');
            $compiler->compileString($template);
            $template = preg_replace('/\\{\\{\\s+/', '{{', $template);
            $template = preg_replace('/\\s+\\}\\}/', '}}', $template);
            $template = replace_short_code($template, $data);
            $tempFile = 'safe_'.Str::random(40).'.blade.php';
            $tempPath = storage_path('framework/views/safe-templates/'.$tempFile);
            if (! is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            file_put_contents($tempPath, $template);
            $processor = app(\Coderstm\Services\ShortcodeProcessor::class);
            $objectData = $processor->toObject($data);
            $rendered = view()->file($tempPath, $objectData)->render();
            @unlink($tempPath);

            return $rendered;
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Template contains disallowed directives or functions: '.$e->getMessage());
        } catch (\Throwable $e) {
            logger()->error('Template rendering failed', ['error' => $e->getMessage(), 'template' => substr($template, 0, 200)]);

            return replace_short_code($template, $data);
        }
    }

    public function validate(string $template): array
    {
        try {
            $compiler = app('blade.compiler');
            $compiler->compileString($template);

            return ['valid' => true];
        } catch (\Throwable $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }
}
