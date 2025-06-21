<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Page;
use Coderstm\Coderstm;
use Coderstm\Models\Blog;
use Illuminate\Http\Request;
use Coderstm\Rules\ReCaptchaRule;
use Illuminate\Support\Facades\Cache;

class WebPageController extends Controller
{
    public $templateRoutes = [
        'home' => '/',
        'blog' => '/blogs',
        'membership' => '/membership',
    ];

    public function index(Request $request)
    {
        return $this->render($request, 'home');
    }

    public function membership(Request $request)
    {
        return $this->render($request, 'membership');
    }

    public function blogs(Request $request)
    {
        return $this->render($request, 'blogs');
    }

    public function blog(Request $request, $slug)
    {
        $blog = Cache::rememberForever("blog_{$slug}", function () use ($slug) {
            return Blog::findBySlug($slug);
        });

        $request->merge(['blog' => $blog]);

        return $this->render($request, 'blog');
    }

    public function pages(Request $request, $slug)
    {
        $page = Cache::rememberForever("page_{$slug}", function () use ($slug) {
            return Page::findBySlug($slug);
        });

        $template = $page->template;

        $request->merge(['page' => $page->toPublic()]);

        if ($template) {
            return redirect()->to($this->templateRoutes[$template] ?? $template);
        }

        return $page->render();
    }

    public function login(Request $request)
    {
        return redirect()->to(app_url('/auth/login'));
    }

    public function contact(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required',
            'phone' => 'required',
            'message' => 'required',
            'recaptcha_token' => ['required', new ReCaptchaRule()]
        ]);

        Coderstm::$enquiryModel::create($request->only([
            'email',
            'name',
            'phone',
            'message',
        ]));

        return redirect()->back()->with('success', 'Your enquiry has been submitted successfully.');
    }

    public function render(Request $request, string $name)
    {
        $page = Cache::rememberForever("page_{$name}", function () use ($name) {
            return Page::findByTemplate($name);
        });

        $request->merge(['page' => $page->toPublic()]);

        return $page->render();
    }
}
