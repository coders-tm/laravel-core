<?php

namespace Coderstm\Shortcodes;

use Vedmant\LaravelShortcodes\Shortcode;

class Menu extends Shortcode
{
    public $attributes = ['class' => ['default' => 'menu'], 'id' => ['default' => 'menu-1']];

    public function render($content)
    {
        $items = [];
        $atts = $this->atts();
        $menus = settings('menus');
        $id = $atts['id'];
        if (isset($menus[$id]) && $menus[$id]) {
            $items = $menus[$id];
        }

        return $this->view('shortcodes.menu', array_merge($atts, ['items' => $this->mapAndFilterData($items)]));
    }

    private function mapAndFilterData($data)
    {
        return array_map(function ($item) {
            $activePattern = [];
            $href = $item['href'];
            if ($href !== '/' && strpos($href, '/') === 0) {
                $href = substr($href, 1);
            }
            if ($this->isValidHref($href)) {
                $activePattern[] = $href;
            }
            if (! empty($item['items'])) {
                $item['items'] = $this->mapAndFilterData($item['items']);
                foreach ($item['items'] as $subItem) {
                    $subHref = $subItem['href'];
                    if ($subHref !== '/' && strpos($subHref, '/') === 0) {
                        $subHref = substr($subHref, 1);
                    }
                    if ($this->isValidHref($subHref)) {
                        $activePattern[] = $subHref;
                    }
                }
            }

            return array_merge($item, ['active' => $this->isActive(...$activePattern)]);
        }, $data);
    }

    private function isValidHref($href)
    {
        return strpos($href, 'http') !== 0 && $href !== '#';
    }

    private function isActive(...$routes)
    {
        return request()->is(...$routes) ? 'active' : '';
    }
}
