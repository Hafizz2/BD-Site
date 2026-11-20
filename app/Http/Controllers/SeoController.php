<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\Page;
use App\Models\Program;
use App\Models\Service;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = [];
            $add = function (string $loc, $lastmod = null, string $freq = 'weekly', string $prio = '0.6') use (&$urls) {
                $urls[] = compact('loc', 'lastmod', 'freq', 'prio');
            };

            $add(route('home'), null, 'daily', '1.0');
            foreach (['about', 'goals', 'services.index', 'programs.index', 'articles.index', 'events.index', 'gallery.index', 'announcements', 'faq', 'contact', 'donate', 'register'] as $r) {
                $add(route($r), null, 'weekly', '0.8');
            }
            Service::public()->get()->each(fn ($s) => $add(route('services.show', $s), $s->updated_at));
            Program::public()->get()->each(fn ($p) => $add(route('programs.show', $p), $p->updated_at));
            ArticleCategory::all()->each(fn ($c) => $add(route('articles.category', $c), $c->updated_at, 'weekly', '0.5'));
            Article::published()->get()->each(fn ($a) => $add(route('articles.show', $a), $a->updated_at, 'monthly', '0.7'));
            Event::published()->get()->each(fn ($e) => $add(route('events.show', $e), $e->updated_at, 'monthly', '0.6'));
            GalleryAlbum::public()->get()->each(fn ($a) => $add(route('gallery.show', $a), $a->updated_at, 'monthly', '0.5'));
            Page::whereNull('key')->where('is_published', true)->get()->each(fn ($p) => $add(route('page', $p), $p->updated_at, 'monthly', '0.5'));

            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach ($urls as $u) {
                $out .= '<url><loc>'.e($u['loc']).'</loc>'
                    .($u['lastmod'] ? '<lastmod>'.$u['lastmod']->toAtomString().'</lastmod>' : '')
                    .'<changefreq>'.$u['freq'].'</changefreq><priority>'.$u['prio'].'</priority></url>';
            }

            return $out.'</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nDisallow: /admin\nDisallow: /register/thanks\nDisallow: /telegram\n\nSitemap: ".route('sitemap')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
