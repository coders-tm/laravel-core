<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Models\Page;
use Coderstm\Coderstm;
use Coderstm\Models\Blog;
use Illuminate\Http\Request;
use Coderstm\Rules\ReCaptchaRule;

class WebPageController extends Controller
{
    public function index()
    {
        $page = $this->getTemplate('home');

        return view('coderstm::pages', $page->render());
    }

    public function pages(Request $request, $slug)
    {
        $page = Page::findBySlug($slug);

        return view('coderstm::pages', $page->render());
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

        return response()->json([
            'success' => true,
            'message' => trans('messages.contact_success_submit')
        ], 200);
    }

    public function blog(Request $request, $slug)
    {
        $page = $this->getTemplate('blog');

        $blog = Blog::findBySlug($slug);

        $request->merge(['blog' => $blog]);

        return view('coderstm::pages', $page->render());
    }

    private function getTemplate(string $name)
    {
        return Page::findByTemplate($name);
    }
}
