<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-03-25 17:32:47              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Shortcodes as Component; use Illuminate\Support\ServiceProvider; use Vedmant\LaravelShortcodes\Facades\Shortcodes; class ShortcodeServiceProvider extends ServiceProvider { public function register() : void { } public function boot() : void { goto mAJj6; mAJj6: Shortcodes::add(["\x70\154\x61\156\163" => Component\Plans::class, "\x63\141\154\145\156\x64\x61\162" => Component\Calendar::class, "\x6f\160\x65\x6e\151\x6e\147\55\164\151\155\145\163" => Component\OpeningTimes::class, "\143\157\x6e\164\x61\x63\164\x2d\x66\157\162\155" => Component\ContactForm::class, "\142\x6c\157\147\163" => Component\Blogs::class, "\150\145\x61\x64\145\162" => Component\Header::class, "\x66\157\157\x74\x65\162" => Component\Footer::class, "\x6d\145\x6e\x75" => Component\Menu::class, "\143\157\x6d\x70\x61\x6e\x79\55\x61\x64\x64\162\145\x73\163" => Component\CompanyAddress::class, "\145\155\x61\151\154" => Component\Email::class, "\160\150\x6f\x6e\x65" => Component\Phone::class, "\163\x6f\x63\x69\x61\x6c\x73" => Component\Socials::class, "\142\154\157\147" => Component\Blog::class, "\162\145\143\145\x6e\164\x2d\x62\x6c\157\147\163" => Component\RecentBlogs::class, "\142\x6c\157\x67\55\x74\141\147\x73" => Component\BlogTags::class]); goto Kc2Q_; Slzgw: Shortcodes::add("\x70\x61\x67\145\55\x74\151\164\154\x65", function ($atts, $content, $tag, $manager) { return request()->input("\160\141\147\145\x2e\x74\x69\164\x6c\145"); }); goto NEtff; Kc2Q_: Shortcodes::add("\142\x6c\157\x67\x2d\164\151\x74\154\145", function ($atts, $content, $tag, $manager) { return request()->input("\142\x6c\x6f\147\56\x74\151\x74\x6c\145"); }); goto Slzgw; NEtff: } }