<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.16  |
    |              on 2025-04-16 10:35:11              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
/*

*/
 namespace Coderstm\Providers; use Coderstm\Shortcodes as Component; use Illuminate\Support\ServiceProvider; use Vedmant\LaravelShortcodes\Facades\Shortcodes; class ShortcodeServiceProvider extends ServiceProvider { public function register() : void { } public function boot() : void { goto hd9XF; SPNPC: Shortcodes::add("\160\x61\147\x65\55\x74\x69\164\x6c\145", function ($atts, $content, $tag, $manager) { return request()->input("\160\141\147\145\56\164\x69\164\x6c\145"); }); goto x8l5o; hd9XF: Shortcodes::add(["\x70\x6c\x61\156\163" => Component\Plans::class, "\143\141\154\x65\156\144\x61\x72" => Component\Calendar::class, "\157\160\145\156\x69\156\x67\x2d\164\151\x6d\145\163" => Component\OpeningTimes::class, "\143\157\x6e\x74\x61\143\164\x2d\146\x6f\162\x6d" => Component\ContactForm::class, "\x62\154\x6f\x67\163" => Component\Blogs::class, "\150\145\141\144\x65\162" => Component\Header::class, "\x66\x6f\x6f\x74\x65\162" => Component\Footer::class, "\x6d\145\x6e\165" => Component\Menu::class, "\143\157\155\x70\141\156\x79\x2d\x61\144\144\162\145\x73\x73" => Component\CompanyAddress::class, "\x65\x6d\141\x69\154" => Component\Email::class, "\160\150\x6f\x6e\x65" => Component\Phone::class, "\163\x6f\143\x69\141\x6c\163" => Component\Socials::class, "\142\x6c\157\147" => Component\Blog::class, "\162\145\x63\145\x6e\164\55\142\x6c\157\x67\163" => Component\RecentBlogs::class, "\142\x6c\157\147\55\x74\141\x67\163" => Component\BlogTags::class]); goto pCJIp; pCJIp: Shortcodes::add("\142\154\157\147\x2d\164\151\x74\x6c\145", function ($atts, $content, $tag, $manager) { return request()->input("\142\154\157\147\x2e\164\x69\x74\x6c\x65"); }); goto SPNPC; x8l5o: } }