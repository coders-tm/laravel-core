<?php

namespace Coderstm\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

trait HasEditor
{
    /**
     * Get the type of editor (e.g., 'pages', 'posts') based on the model class name.
     *
     * @return string
     */
    public function type(): string
    {
        return Str::of(class_basename(static::class))->slug()->plural();
    }

    /**
     * Get the path where the Blade view will be stored.
     *
     * @return string
     */
    public function viewPath(): string
    {
        return resource_path("views/{$this->type()}/{$this->id}.blade.php");
    }

    /**
     * Retrieve the Blade view name.
     *
     * @return string
     */
    public function viewName(): string
    {
        return $this->type() . '.' . $this->id;
    }

    /**
     * Publish the page data by saving it as a Blade view file.
     *
     * @param array $data
     * @return void
     */
    public function publish(array $data): void
    {
        $content = '';
        $style = $data['css'] ?? '';

        $bodyPattern = '/<body\b[^>]*>(.*?)<\/body>/is';
        if (preg_match($bodyPattern, $data['body'], $bodyMatch)) {
            $content = $bodyMatch[1]; // This gets the content inside <body> tags
        }

        // Load the page stub template
        $pageStub = resource_path('page.stub');
        $stubContent = File::get($pageStub);

        // Replace placeholders in the stub with actual content
        $parsedContent = str_replace(
            ['{{ style }}', '{{ content }}'],
            [$style, $content],
            $stubContent
        );

        // Save the parsed content as a Blade view file
        static::put($this->viewPath(), $parsedContent);
    }

    public function toPublic(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'meta_title' => $this->meta_title,
            'meta_keywords' => $this->meta_keywords,
            'meta_description' => $this->meta_description,
        ];
    }

    /**
     * Render the page view.
     *
     * @param array $data
     * @return \Illuminate\View\View
     */
    public function render(array $data = [])
    {
        return view($this->viewName(), array_merge($data, $this->toPublic()));
    }

    /**
     * Write content to a file, ensuring the directory exists.
     *
     * @param string $path
     * @param string $content
     * @return void
     */
    public static function put(string $path, string $content): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }
}
