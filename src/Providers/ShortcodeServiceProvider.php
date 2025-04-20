<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-20 18:18:19              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Shortcodes as Component; use Illuminate\Support\ServiceProvider; use Vedmant\LaravelShortcodes\Facades\Shortcodes; class ShortcodeServiceProvider extends ServiceProvider { public function register() : void { } public function boot() : void { goto ECXYA; Fl1kV: Shortcodes::add("\x62\154\157\x67\55\x74\x69\x74\x6c\145", function ($atts, $content, $tag, $manager) { return request()->input("\x62\x6c\x6f\147\56\x74\x69\164\154\x65"); }); goto wlopu; ECXYA: Shortcodes::add(["\160\x6c\x61\156\x73" => Component\Plans::class, "\x63\141\154\x65\156\x64\x61\162" => Component\Calendar::class, "\x6f\160\x65\x6e\x69\x6e\147\x2d\x74\151\155\x65\163" => Component\OpeningTimes::class, "\x63\157\156\164\141\143\x74\x2d\146\157\x72\155" => Component\ContactForm::class, "\142\154\157\147\x73" => Component\Blogs::class, "\150\x65\x61\x64\145\162" => Component\Header::class, "\x66\x6f\157\164\145\x72" => Component\Footer::class, "\x6d\x65\156\x75" => Component\Menu::class, "\x63\x6f\155\x70\x61\x6e\x79\x2d\141\x64\144\162\145\x73\x73" => Component\CompanyAddress::class, "\x65\x6d\141\151\x6c" => Component\Email::class, "\160\x68\157\156\145" => Component\Phone::class, "\163\x6f\143\x69\141\154\x73" => Component\Socials::class, "\142\x6c\157\x67" => Component\Blog::class, "\162\x65\143\x65\156\164\x2d\x62\154\157\147\x73" => Component\RecentBlogs::class, "\x62\x6c\x6f\x67\55\164\141\x67\x73" => Component\BlogTags::class]); goto Fl1kV; wlopu: Shortcodes::add("\x70\x61\x67\x65\x2d\164\151\x74\x6c\145", function ($atts, $content, $tag, $manager) { return request()->input("\x70\x61\147\x65\56\x74\x69\x74\154\x65"); }); goto LAZOA; LAZOA: } }