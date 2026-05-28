<?php

namespace Coderstm\Services;

use Coderstm\Coderstm;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;

class SafeFluent extends Fluent
{
    public function __toString(): string
    {
        return '';
    }
}
class ShortcodeProcessor
{
    public function process(array $data = []): array
    {
        $defaultData = $this->getDefaultData();
        if (isset(Coderstm::$appShortCodes) && is_array(Coderstm::$appShortCodes)) {
            $defaultData = $this->mergeRecursive($defaultData, Coderstm::$appShortCodes);
        }
        $data = $this->mergeRecursive($defaultData, $data);

        return $this->buildReplacements($data);
    }

    protected function mergeRecursive(array $defaults, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])) {
                $defaults[$key] = $this->mergeRecursive($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    protected function getDefaultData(): array
    {
        return ['app' => ['domain' => config('coderstm.domain'), 'email' => config('coderstm.admin_email'), 'name' => config('app.name'), 'url' => config('app.url')], 'support' => ['email' => config('coderstm.admin_email')], 'pages' => ['billing' => app_url('billing'), 'member' => config('app.url'), 'admin' => config('coderstm.admin_url')]];
    }

    protected function buildReplacements(array $data): array
    {
        $replacements = [];
        $scalarQueue = [];
        foreach ($data as $key => $value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $this->processModelShortcodes($replacements, $key, $value);
            } elseif (is_array($value)) {
                $this->processArrayShortcodes($replacements, $key, $value);
                if ($key === 'pages') {
                    $this->processPageShortcodes($replacements, $value);
                }
            } elseif (is_scalar($value) || is_null($value)) {
                $scalarQueue[$key] = $value;
            }
        }
        foreach ($scalarQueue as $key => $value) {
            $this->processScalarShortcode($replacements, $key, $value);
        }

        return $replacements;
    }

    protected function processModelShortcodes(array &$replacements, string $key, object $model): void
    {
        $prefixUpper = strtoupper($key);
        foreach ($model->toArray() as $attr => $val) {
            if (is_scalar($val) || is_null($val)) {
                $replacements['{{'.$prefixUpper.'_'.strtoupper($attr).'}}'] = $val ?? '';
            }
        }
        if (method_exists($model, '__toString')) {
            $replacements['{{'.$prefixUpper.'}}'] = (string) $model;
        }
    }

    protected function processArrayShortcodes(array &$replacements, string $key, array $data): void
    {
        $prefixUpper = strtoupper($key);
        foreach ($data as $subKey => $subVal) {
            if (is_scalar($subVal) || is_null($subVal)) {
                $replacements['{{'.$prefixUpper.'_'.strtoupper($subKey).'}}'] = $subVal ?? '';
            }
        }
    }

    protected function processPageShortcodes(array &$replacements, array $pages): void
    {
        foreach ($pages as $pageKey => $pageValue) {
            if (is_scalar($pageValue) || is_null($pageValue)) {
                $replacements['{{'.strtoupper($pageKey).'_PAGE}}'] = $pageValue ?? '';
            }
        }
    }

    protected function processScalarShortcode(array &$replacements, string $key, $value): void
    {
        $replacements['{{'.strtoupper($key).'}}'] = $value ?? '';
    }

    public function toObject(array $data = []): array
    {
        $defaultData = $this->getDefaultData();
        if (isset(Coderstm::$appShortCodes) && is_array(Coderstm::$appShortCodes)) {
            $defaultData = $this->mergeRecursive($defaultData, Coderstm::$appShortCodes);
        }
        $data = $this->mergeRecursive($defaultData, $data);
        $converted = [];
        foreach ($data as $k => $v) {
            $converted[$k] = $this->convertValue($v);
        }

        return $converted;
    }

    protected function convertValue($value)
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            $value = $value->toArray();
        }
        if (is_array($value)) {
            $isAssoc = Arr::isAssoc($value);
            if ($isAssoc) {
                $converted = [];
                foreach ($value as $k => $v) {
                    $converted[$k] = $this->convertValue($v);
                }

                return new SafeFluent($converted);
            }

            return array_map(fn ($v) => $this->convertValue($v), $value);
        }

        return $value;
    }
}
