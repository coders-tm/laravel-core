<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-20 17:43:37              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Shortcodes as Component; use Illuminate\Support\ServiceProvider; use Vedmant\LaravelShortcodes\Facades\Shortcodes; class ShortcodeServiceProvider extends ServiceProvider { public function register() : void { } public function boot() : void { goto PFJml; Bwzza: Shortcodes::add("\160\141\147\x65\x2d\x74\x69\164\x6c\x65", function ($atts, $content, $tag, $manager) { return request()->input("\x70\141\x67\x65\x2e\164\x69\164\x6c\145"); }); goto HEP4p; PFJml: Shortcodes::add(["\x70\x6c\x61\x6e\x73" => Component\Plans::class, "\x63\141\154\145\156\x64\141\162" => Component\Calendar::class, "\x6f\x70\145\156\151\156\147\x2d\164\151\155\x65\x73" => Component\OpeningTimes::class, "\x63\x6f\156\164\x61\x63\x74\55\x66\x6f\162\x6d" => Component\ContactForm::class, "\142\154\x6f\x67\163" => Component\Blogs::class, "\x68\x65\141\144\x65\x72" => Component\Header::class, "\x66\157\157\x74\145\x72" => Component\Footer::class, "\155\x65\156\165" => Component\Menu::class, "\143\157\x6d\160\x61\156\171\x2d\x61\144\144\162\x65\x73\x73" => Component\CompanyAddress::class, "\x65\x6d\x61\151\154" => Component\Email::class, "\x70\x68\x6f\156\x65" => Component\Phone::class, "\163\157\143\151\141\x6c\x73" => Component\Socials::class, "\x62\154\157\x67" => Component\Blog::class, "\x72\x65\x63\145\156\x74\x2d\x62\154\x6f\x67\163" => Component\RecentBlogs::class, "\x62\154\157\x67\x2d\164\141\147\163" => Component\BlogTags::class]); goto AtDWM; AtDWM: Shortcodes::add("\142\x6c\x6f\147\55\164\x69\x74\x6c\145", function ($atts, $content, $tag, $manager) { return request()->input("\x62\154\x6f\x67\56\x74\x69\x74\154\x65"); }); goto Bwzza; HEP4p: } }