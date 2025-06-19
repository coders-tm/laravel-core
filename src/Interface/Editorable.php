<?php

namespace Coderstm\Interface;

interface Editorable
{
    /**
     * Get the type of editor (e.g., 'pages', 'posts') based on the model class name.
     *
     * @return string
     */
    public function type(): string;

    /**
     * Get the path where the Blade view will be stored.
     *
     * @return string
     */
    public function viewPath(): string;

    /**
     * Retrieve the Blade view name.
     *
     * @return string
     */
    public function viewName(): string;

    /**
     * Publish the page data by saving it as a Blade view file.
     *
     * @param array $data
     * @return void
     */
    public function publish(array $data): void;

    /**
     * Render the page view.
     *
     * @param array $data
     * @return \Illuminate\View\View
     */
    public function render(array $data = []);

    /**
     * Write content to a file, ensuring the directory exists.
     *
     * @param string $path
     * @param string $content
     * @return void
     */
    public static function put(string $path, string $content): void;
}
