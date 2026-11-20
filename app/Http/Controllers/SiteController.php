<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Event;
use App\Models\Faq;
use App\Models\GalleryAlbum;
use App\Models\Goal;
use App\Models\JourneyStep;
use App\Models\Member;
use App\Models\Page;
use App\Models\Program;
use App\Models\Service;
use App\Models\Stat;
use App\Settings\HomepageSettings;
use App\Settings\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(HomepageSettings $home): View
    {
        $limit = fn (string $key, int $default) => (int) ($home->sections[$key]['limit'] ?? $default) ?: $default;

        return view('site.home', [
            'stats' => Stat::public()->get(),
            'services' => $home->enabled('services') ? Service::public()->orderByDesc('is_featured')->orderBy('sort')->take($limit('services', 7))->get() : collect(),
            'goals' => $home->enabled('goals') ? Goal::public()->take($limit('goals', 4))->get() : collect(),
            'steps' => $home->enabled('journey') ? JourneyStep::orderBy('sort')->take($limit('journey', 6))->get() : collect(),
            'articles' => $home->enabled('articles') ? Article::published()->with(['category', 'media'])->take($limit('articles', 3))->get() : collect(),
            'events' => $home->enabled('events') ? Event::upcoming()->with('media')->take($limit('events', 3))->get() : collect(),
            'albums' => $home->enabled('gallery') ? GalleryAlbum::public()->with('media')->orderByDesc('is_featured')->take($limit('gallery', 5))->get() : collect(),
        ]);
    }

    public function about(): View
    {
        return view('site.about', [
            'page' => Page::system('about'),
            'stats' => Stat::public()->get(),
            'steps' => JourneyStep::orderBy('sort')->get(),
        ]);
    }

    public function goals(): View
    {
        return view('site.goals', ['page' => Page::system('goals'), 'goals' => Goal::public()->get()]);
    }

    public function services(): View
    {
        return view('site.services.index', ['page' => Page::system('services'), 'services' => Service::public()->get()]);
    }

    public function service(Service $service): View
    {
        abort_unless($service->is_active, 404);

        return view('site.services.show', [
            'service' => $service->load('program'),
            'servicesPage' => Page::system('services'),
            'others' => Service::public()->whereKeyNot($service->id)->take(3)->get(),
        ]);
    }

    public function programs(): View
    {
        return view('site.programs.index', [
            'page' => Page::system('programs'),
            'programs' => Program::public()->with('media')->withCount('openBatches')->get(),
        ]);
    }

    public function program(Program $program): View
    {
        abort_unless($program->is_active && $program->show_on_website, 404);

        return view('site.programs.show', [
            'program' => $program,
            'programsPage' => Page::system('programs'),
            'batches' => $program->openBatches()
                ->withCount(['enrollments as approved_count' => fn ($q) => $q->whereIn('status', ['approved', 'completed'])])
                ->orderBy('start_date')->get(),
            'events' => Event::upcoming()->where('program_id', $program->id)->take(3)->get(),
        ]);
    }

    public function articles(Request $request, ?ArticleCategory $category = null): View
    {
        $q = trim((string) $request->query('q', ''));
        $query = Article::published()->with(['category', 'media', 'author']);

        if ($category) {
            $query->where('article_category_id', $category->id);
        }
        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('title_am', 'like', $like)
                ->orWhere('excerpt', 'like', $like)->orWhere('excerpt_am', 'like', $like)
                ->orWhere('body', 'like', $like)->orWhere('body_am', 'like', $like));
        }

        // A big "featured" story on the first unfiltered page.
        $featured = null;
        if (! $category && $q === '' && ! $request->has('page')) {
            $featured = (clone $query)->where('is_featured', true)->first() ?? (clone $query)->first();
            if ($featured) {
                $query->whereKeyNot($featured->id);
            }
        }

        return view('site.articles.index', [
            'page' => Page::system('articles'),
            'category' => $category,
            'categories' => ArticleCategory::withCount(['articles' => fn ($a) => $a->published()])->whereHas('articles', fn ($a) => $a->published())->orderBy('sort')->get(),
            'featured' => $featured,
            'articles' => $query->paginate(9)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function article(Article $article): View
    {
        abort_unless($article->status->value === 'published' && $article->published_at && $article->published_at->lte(now()), 404);
        Article::whereKey($article->id)->increment('views');

        return view('site.articles.show', [
            'article' => $article->load(['category', 'author']),
            'articlesPage' => Page::system('articles'),
            'related' => Article::published()->with(['category', 'media'])->whereKeyNot($article->id)
                ->when($article->article_category_id, fn ($q) => $q->reorder()->orderByRaw('case when article_category_id = ? then 0 else 1 end', [$article->article_category_id])->latest('published_at'))
                ->take(3)->get(),
        ]);
    }

    public function events(): View
    {
        return view('site.events.index', [
            'page' => Page::system('events'),
            'upcoming' => Event::upcoming()->with('media')->get(),
            'past' => Event::past()->with('media')->paginate(9),
        ]);
    }

    public function event(Event $event): View
    {
        abort_unless($event->is_published, 404);

        return view('site.events.show', [
            'event' => $event->load('program'),
            'eventsPage' => Page::system('events'),
            'more' => Event::upcoming()->whereKeyNot($event->id)->take(3)->get(),
        ]);
    }

    public function eventIcs(Event $event): Response
    {
        abort_unless($event->is_published, 404);
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $esc = fn ($s) => addcslashes((string) $s, ",;\\");
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Ye Birhan Dana//Events//EN', 'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@'.request()->getHost(),
            'DTSTAMP:'.$fmt(now()),
            'DTSTART:'.$fmt($event->starts_at),
            'DTEND:'.$fmt($event->ends_at ?? $event->starts_at->copy()->addHours(2)),
            'SUMMARY:'.$esc($event->t('title')),
            'LOCATION:'.$esc($event->t('location')),
            'DESCRIPTION:'.$esc(Str::limit(strip_tags((string) $event->t('summary')), 300)),
            'URL:'.route('events.show', $event),
            'END:VEVENT', 'END:VCALENDAR',
        ]);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$event->slug.'.ics"',
        ]);
    }

    public function gallery(): View
    {
        return view('site.gallery.index', [
            'page' => Page::system('gallery'),
            'albums' => GalleryAlbum::public()->with('media')->withCount('media')->paginate(12),
        ]);
    }

    public function album(GalleryAlbum $album): View
    {
        // Privacy: albums with minors are hidden until consent is confirmed.
        abort_unless($album->isPubliclyVisible(), 404);

        return view('site.gallery.show', [
            'album' => $album,
            'galleryPage' => Page::system('gallery'),
            'photos' => $album->getMedia('photos'),
        ]);
    }

    public function announcements(): View
    {
        return view('site.announcements', [
            'page' => Page::system('announcements'),
            'announcements' => Announcement::onWebsite()->with('media')->paginate(10),
        ]);
    }

    public function faq(): View
    {
        return view('site.faq', ['page' => Page::system('faq'), 'faqs' => Faq::public()->get()]);
    }

    public function donate(PaymentSettings $payment): View
    {
        return view('site.donate', [
            'page' => Page::system('donate'),
            'payment' => $payment,
            'accounts' => $payment->donateAccounts(),
        ]);
    }

    public function page(Page $page): View
    {
        abort_unless($page->is_published, 404);
        // System pages have their own routes.
        if ($page->key && $page->key !== 'home' && \Illuminate\Support\Facades\Route::has($page->key)) {
            abort(404);
        }

        return view('site.page', ['page' => $page]);
    }
}
