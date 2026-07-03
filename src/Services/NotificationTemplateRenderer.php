<?php

namespace Coderstm\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class NotificationTemplateRenderer
{
    /**
     * Render a template with safe Blade compilation using MaskSensitiveConfig
     */
    public function render(string $template, array $data = []): string
    {
        try {
            // Get default Blade compiler (now MaskSensitiveConfig with security)
            $compiler = new MaskSensitiveConfig(app(Filesystem::class), storage_path('framework/views'));
            $compiler->forceSecure = true;

            // Fix common HTML escaped characters in placeholders before rendering
            // This handles cases where people edit templates in a UI that escapes these
            $template = str_replace(['-&gt;', '&amp;'], ['->', '&'], $template);

            // Compile to PHP (performs validation and masking/security transforms)
            $compiledPhp = $compiler->compileString($template);

            // Normalize whitespace inside moustaches to support variants like "{{ APP_NAME }}"
            // (Only if not already processed by compileString)
            $template = preg_replace('/\{\{\s+/', '{{', $template);
            $template = preg_replace('/\s+\}\}/', '}}', $template);

            // Replace UPPERCASE shortcodes first (before rendering)
            // Pass data to helper which will process it internally
            // Since we compiled to PHP first, we should replace shortcodes in the compiled output or prior to compilation.
            // Replacing prior to compilation is standard, but since we are compiling first, let's do it on the raw template or compiled.
            // Let's replace shortcodes on the template before compileString, or we can compile the template with shortcodes replaced.
            // Actually, replacing shortcodes on the template before compilation is cleaner:
            $template = replace_short_code($template, $data);
            $compiledPhp = $compiler->compileString($template);

            // Create a unique temporary filename (plain .php extension to bypass Blade compiling)
            $tempFile = 'safe_'.Str::random(40).'.php';
            $tempPath = storage_path('framework/views/safe-templates/'.$tempFile);

            // Ensure directory exists
            if (! is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            // Write compiled PHP code to temp file
            file_put_contents($tempPath, $compiledPhp);

            // Convert arrays to object form for Blade access ($obj->key)
            $processor = app(ShortcodeProcessor::class);
            $objectData = $processor->toObject($data);
            $objectData['__env'] = view();

            // Render using the plain PHP engine
            $rendered = view()->getEngineResolver()->resolve('php')->get($tempPath, $objectData);

            return $rendered;
        } catch (\InvalidArgumentException $e) {
            // Security validation failed - don't render, just throw
            throw new \InvalidArgumentException(
                'Template contains disallowed directives or functions: '.$e->getMessage()
            );
        } catch (\Throwable $e) {
            // Log the error
            logger()->error('Template rendering failed', [
                'error' => $e->getMessage(),
                'template' => substr($template, 0, 200),
            ]);

            // Fallback to plain shortcode replacement on original template
            return replace_short_code($template, $data);
        } finally {
            if (isset($tempPath)) {
                // Clean up temp file
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            }
        }
    }

    /**
     * Validate template syntax without rendering
     */
    public function validate(string $template): array
    {
        try {
            $compiler = new MaskSensitiveConfig(app(Filesystem::class), storage_path('framework/views'));
            $compiler->forceSecure = true;
            $compiler->compileString($template);

            return ['valid' => true];
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
