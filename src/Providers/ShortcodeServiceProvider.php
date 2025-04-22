<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-22 01:53:54              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Shortcodes as Component; use Illuminate\Support\ServiceProvider; use Vedmant\LaravelShortcodes\Facades\Shortcodes; class ShortcodeServiceProvider extends ServiceProvider { public function register() : void { } public function boot() : void { goto NOMAi; NOMAi: Shortcodes::add(["\x70\x6c\141\x6e\163" => Component\Plans::class, "\x63\141\x6c\145\x6e\x64\141\162" => Component\Calendar::class, "\157\x70\145\156\151\156\147\x2d\x74\151\x6d\145\163" => Component\OpeningTimes::class, "\x63\x6f\156\x74\x61\x63\164\55\146\x6f\x72\155" => Component\ContactForm::class, "\x62\x6c\157\147\163" => Component\Blogs::class, "\x68\x65\x61\x64\145\x72" => Component\Header::class, "\x66\x6f\157\x74\145\162" => Component\Footer::class, "\x6d\145\x6e\x75" => Component\Menu::class, "\x63\x6f\x6d\160\x61\156\171\x2d\x61\144\144\x72\x65\163\x73" => Component\CompanyAddress::class, "\x65\x6d\141\151\154" => Component\Email::class, "\x70\150\157\x6e\x65" => Component\Phone::class, "\x73\x6f\143\x69\x61\154\x73" => Component\Socials::class, "\142\x6c\157\x67" => Component\Blog::class, "\x72\x65\x63\x65\x6e\x74\55\142\154\x6f\x67\x73" => Component\RecentBlogs::class, "\142\154\157\147\55\x74\141\147\163" => Component\BlogTags::class]); goto ydMM5; m_1Nv: Shortcodes::add("\160\x61\147\x65\55\164\151\164\154\x65", function ($atts, $content, $tag, $manager) { return request()->input("\x70\141\x67\x65\56\x74\x69\x74\154\145"); }); goto Hvrlc; ydMM5: Shortcodes::add("\x62\x6c\x6f\x67\x2d\164\x69\x74\154\145", function ($atts, $content, $tag, $manager) { return request()->input("\142\x6c\157\x67\x2e\x74\x69\x74\x6c\145"); }); goto m_1Nv; Hvrlc: } }