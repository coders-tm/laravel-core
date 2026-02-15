<?php

namespace Coderstm\Providers;

use Coderstm\Coderstm;
use Coderstm\Data\CartData;
use Coderstm\Facades\Shop;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function register() {}

    public function boot()
    {
        View::composer('*', function ($view) {
            $view->with('blog', request()->input('blog'));
            if (Coderstm::shouldEnableCart()) {
                try {
                    $view->with('cart', Shop::cart());
                } catch (\Throwable $e) {
                    $view->with('cart', new CartData);
                    logger()->error('Shop::cart Error: '.$e->getMessage());
                }
            } else {
                $view->with('cart', new CartData);
            }
        });
    }
}
