<?php

namespace Coderstm\Contracts;

interface Editorable
{
    public function viewPath(): string;

    public function viewName(): string;

    public function publish(array $data): void;

    public function render(array $data = []);

    public static function put(string $path, string $content): void;
}
