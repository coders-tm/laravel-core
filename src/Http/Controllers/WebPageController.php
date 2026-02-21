<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Coderstm;
use Coderstm\Models\Blog;
use Coderstm\Rules\ReCaptchaRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WebPageController extends Controller
{
    public function index(Request $request)
    {
        if ($view = settings('reading.homepage.view')) {
            return view("pages.{$view}", $request->input());
        }

        return view('pages.home', $request->input());
    }

    public function blogs(Request $request)
    {
        try {
            return view('pages.blogs', $request->input());
        } catch (\Throwable $e) {
            return abort(404);
        }
    }

    public function blog(Request $request, $slug)
    {
        $blog = Cache::rememberForever("blog_{$slug}", function () use ($slug) {
            return Blog::findBySlug($slug);
        });
        $request->merge(['blog' => $blog]);
        try {
            return view('pages.blog', $blog);
        } catch (\Throwable $e) {
            return abort(404);
        }
    }

    public function pages(Request $request, $slug)
    {
        if (! preg_match('/^[a-z0-9\\-_]+$/i', $slug)) {
            abort(404);
        }
        try {
            return view("pages.{$slug}", $request->input());
        } catch (\Throwable $th) {
            abort(404);
        }
    }

    public function contact(Request $request)
    {
        $request->validate(['email' => 'required|email', 'name' => 'required', 'phone' => 'required', 'message' => 'required', 'recaptcha_token' => ['required', new ReCaptchaRule]]);
        Coderstm::$enquiryModel::create($request->only(['email', 'name', 'phone', 'message']));

        return redirect()->back()->with('success', 'Your enquiry has been submitted successfully.');
    }
}
