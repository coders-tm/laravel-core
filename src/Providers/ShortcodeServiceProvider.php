<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-20 18:09:16              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Shortcodes as Component; use Illuminate\Support\ServiceProvider; use Vedmant\LaravelShortcodes\Facades\Shortcodes; class ShortcodeServiceProvider extends ServiceProvider { public function register() : void { } public function boot() : void { goto yFA0D; uOD3E: Shortcodes::add("\160\x61\x67\x65\55\164\x69\x74\x6c\x65", function ($atts, $content, $tag, $manager) { return request()->input("\x70\x61\x67\145\x2e\164\x69\164\154\x65"); }); goto B4kEi; yFA0D: Shortcodes::add(["\x70\x6c\141\x6e\x73" => Component\Plans::class, "\143\141\154\145\156\x64\141\x72" => Component\Calendar::class, "\157\x70\145\x6e\x69\156\147\x2d\164\151\155\145\x73" => Component\OpeningTimes::class, "\x63\x6f\x6e\x74\141\x63\x74\55\146\157\x72\155" => Component\ContactForm::class, "\x62\x6c\157\147\163" => Component\Blogs::class, "\x68\x65\x61\144\x65\162" => Component\Header::class, "\146\157\x6f\164\145\162" => Component\Footer::class, "\155\145\x6e\165" => Component\Menu::class, "\x63\157\x6d\160\141\x6e\x79\55\x61\144\144\162\x65\x73\163" => Component\CompanyAddress::class, "\145\155\x61\151\154" => Component\Email::class, "\x70\150\x6f\156\x65" => Component\Phone::class, "\x73\157\x63\x69\141\154\x73" => Component\Socials::class, "\142\x6c\x6f\x67" => Component\Blog::class, "\162\145\x63\145\156\x74\x2d\x62\154\x6f\147\x73" => Component\RecentBlogs::class, "\142\154\x6f\147\55\x74\x61\x67\163" => Component\BlogTags::class]); goto Ox445; Ox445: Shortcodes::add("\142\154\157\147\x2d\164\x69\x74\x6c\145", function ($atts, $content, $tag, $manager) { return request()->input("\x62\x6c\157\147\x2e\x74\x69\164\x6c\145"); }); goto uOD3E; B4kEi: } }