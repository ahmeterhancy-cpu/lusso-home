<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = [route('home'), route('shop'), route('contact'), route('blog')];
        foreach (Category::active()->get(['slug']) as $c) {
            $urls[] = $c->url();
        }
        foreach (Page::where('is_active', true)->get(['slug']) as $p) {
            $urls[] = route('page', $p->slug);
        }
        foreach (Product::visible()->get(['slug']) as $p) {
            $urls[] = $p->url();
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml .= '<url><loc>'.e($u).'</loc></url>';
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml']);
    }
}
