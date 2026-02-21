<?php

namespace Coderstm\Traits;

use Illuminate\Support\Facades\File;

trait HasEditor
{
    public static function bootHasEditor()
    {
        static::updated(function ($model) {
            if ($model->isDirty('slug') && $model->getOriginal('slug')) {
                $originalSlug = $model->getOriginal('slug');
                $newSlug = $model->slug;
                $oldViewPath = resource_path("views/pages/{$originalSlug}.blade.php");
                $newViewPath = resource_path("views/pages/{$newSlug}.blade.php");
                if (File::exists($oldViewPath)) {
                    File::move($oldViewPath, $newViewPath);
                }
                $oldJsonPath = resource_path("views/pages/{$originalSlug}.json");
                $newJsonPath = resource_path("views/pages/{$newSlug}.json");
                if (File::exists($oldJsonPath)) {
                    File::move($oldJsonPath, $newJsonPath);
                }
            }
        });
        static::saved(function ($model) {
            $model->publishJson();
        });
        static::deleted(function ($model) {
            if (File::exists($model->viewPath())) {
                File::delete($model->viewPath());
            }
            if (File::exists($model->jsonPath())) {
                File::delete($model->jsonPath());
            }
            $model->removeFromPagesRegistry();
        });
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->publishJson();
            });
        }
    }

    public function pagesPath($path = null): string
    {
        if (str_starts_with($path, '/')) {
            $path = substr($path, 1);
        }

        return config('coderstm.editor.pages_path').'/'.($path ? $path : '');
    }

    public function viewPath(): string
    {
        return $this->pagesPath("{$this->slug}.blade.php");
    }

    public function viewName(): string
    {
        return 'pages.'.$this->slug;
    }

    public function publish(array $data): void
    {
        $content = '';
        $id = '';
        $class = '';
        $style = $data['css'] ?? '';
        $script = $data['script'] ?? '';
        $body = $data['body'] ?? '';
        $this->validateContent($body);
        $this->validateContent($style);
        if (preg_match('/\\bid="([^"]*)"/', $body, $idMatches)) {
            $id = $idMatches[1];
        }
        if (preg_match('/\\bclass="([^"]*)"/', $body, $classMatches)) {
            $class = $classMatches[1];
        }
        $bodyPattern = '/<body\\b[^>]*>(.*?)<\\/body>/is';
        if (preg_match($bodyPattern, $body, $bodyMatch)) {
            $content = $bodyMatch[1];
        }
        $pageStub = resource_path('page.stub');
        $stubContent = File::get($pageStub);
        $dataContent = var_export($this->toPublic(), true);
        $parsedContent = str_replace(['{{ style }}', '{{ content }}', '{{ id }}', '{{ class }}', '{{ script }}', '{{ data }}'], [$style, $content, $id, $class, $script, $dataContent], $stubContent);
        static::put($this->viewPath(), $parsedContent);
        $this->publishJson();
    }

    public function jsonPath(): string
    {
        return $this->pagesPath("{$this->slug}.json");
    }

    public function publishJson(array $data = []): void
    {
        if (empty($data)) {
            $data = $this->data;
        }
        $payload = ['id' => $this->id, 'slug' => $this->slug, 'parent' => $this->parent ?? null, 'title' => $this->title, 'meta_title' => $this->meta_title, 'meta_keywords' => $this->meta_keywords, 'meta_description' => $this->meta_description, 'data' => $data];
        static::put($this->jsonPath(), json_encode($payload, JSON_PRETTY_PRINT));
        $this->updatePagesRegistry();
    }

    protected function updatePagesRegistry(): void
    {
        $registryPath = config('coderstm.editor.registry_path');
        $registry = [];
        if (File::exists($registryPath)) {
            $registry = json_decode(File::get($registryPath), true) ?: [];
        }
        $registry[$this->slug] = ['id' => $this->id, 'slug' => $this->slug, 'parent' => $this->parent ?? null, 'template' => $this->template ?? null, 'title' => $this->title, 'meta_title' => $this->meta_title, 'meta_keywords' => $this->meta_keywords, 'meta_description' => $this->meta_description, 'options' => $this->options];
        static::put($registryPath, json_encode($registry, JSON_PRETTY_PRINT));
    }

    protected function removeFromPagesRegistry(): void
    {
        $registryPath = config('coderstm.editor.registry_path');
        if (! File::exists($registryPath)) {
            return;
        }
        $registry = json_decode(File::get($registryPath), true) ?: [];
        if (isset($registry[$this->slug])) {
            unset($registry[$this->slug]);
        }
        if (empty($registry)) {
            File::delete($registryPath);
        } else {
            static::put($registryPath, json_encode($registry, JSON_PRETTY_PRINT));
        }
    }

    public function validateContent(string $content): void
    {
        $compiler = app('blade.compiler');
        $compiler->compileString($content);
    }

    public function toPublic(): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'slug' => $this->slug, 'parent' => $this->parent ?? null, 'template' => $this->template ?? null, 'meta_title' => $this->meta_title, 'meta_keywords' => $this->meta_keywords, 'meta_description' => $this->meta_description, 'seo' => ['title' => $this->meta_title, 'keywords' => $this->meta_keywords, 'description' => $this->meta_description], 'options' => $this->options];
    }

    public function getDataAttribute($value)
    {
        $data = $value ?? [];
        if (File::exists($this->jsonPath())) {
            $json = json_decode(File::get($this->jsonPath()));
            $data = $json->data;
        }

        return $data;
    }

    public function render(array $data = [])
    {
        return view($this->viewName(), array_merge($data, $this->toPublic()));
    }

    public static function put(string $path, string $content): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }
}
