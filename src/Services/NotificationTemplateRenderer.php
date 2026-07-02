<?php

namespace Coderstm\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class NotificationTemplateRenderer
{
    public function render(string $template, array $data = []): string
    {
        try {
            $compiler = new MaskSensitiveConfig(app(Filesystem::class), storage_path('framework/views'));
            $compiler->forceSecure = true;
            $template = str_replace(['-&gt;', '&amp;'], ['->', '&'], $template);
            $compiledPhp = $compiler->compileString($template);
            $template = preg_replace('/\\{\\{\\s+/', '{{', $template);
            $template = preg_replace('/\\s+\\}\\}/', '}}', $template);
            $template = replace_short_code($template, $data);
            $compiledPhp = $compiler->compileString($template);
            $tempFile = 'safe_'.Str::random(40).'.php';
            $tempPath = storage_path('framework/views/safe-templates/'.$tempFile);
            if (! is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            file_put_contents($tempPath, $compiledPhp);
            $processor = app(ShortcodeProcessor::class);
            $objectData = $processor->toObject($data);
            $objectData['__env'] = view();
            $rendered = view()->getEngineResolver()->resolve('php')->get($tempPath, $objectData);

            return $rendered;
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Template contains disallowed directives or functions: '.$e->getMessage());
        } catch (\Throwable $e) {
            logger()->error('Template rendering failed', ['error' => $e->getMessage(), 'template' => substr($template, 0, 200)]);

            return replace_short_code($template, $data);
        } finally {
            if (isset($tempPath)) {
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            }
        }
    }

    public function validate(string $template): array
    {
        try {
            $compiler = new MaskSensitiveConfig(app(Filesystem::class), storage_path('framework/views'));
            $compiler->forceSecure = true;
            $compiler->compileString($template);

            return ['valid' => true];
        } catch (\Throwable $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }
}
