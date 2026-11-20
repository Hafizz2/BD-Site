<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\PrivateMediaController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website (EN / AM)
|--------------------------------------------------------------------------
*/
Route::middleware(SetLocale::class)->group(function () {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/about', [SiteController::class, 'about'])->name('about');
    Route::get('/goals', [SiteController::class, 'goals'])->name('goals');
    Route::get('/services', [SiteController::class, 'services'])->name('services.index');
    Route::get('/services/{service:slug}', [SiteController::class, 'service'])->name('services.show');
    Route::get('/programs', [SiteController::class, 'programs'])->name('programs.index');
    Route::get('/programs/{program:slug}', [SiteController::class, 'program'])->name('programs.show');
    Route::get('/articles', [SiteController::class, 'articles'])->name('articles.index');
    Route::get('/articles/category/{category:slug}', [SiteController::class, 'articles'])->name('articles.category');
    Route::get('/articles/{article:slug}', [SiteController::class, 'article'])->name('articles.show');
    Route::get('/events', [SiteController::class, 'events'])->name('events.index');
    Route::get('/events/{event:slug}', [SiteController::class, 'event'])->name('events.show');
    Route::get('/events/{event:slug}/calendar.ics', [SiteController::class, 'eventIcs'])->name('events.ics');
    Route::get('/gallery', [SiteController::class, 'gallery'])->name('gallery.index');
    Route::get('/gallery/{album:slug}', [SiteController::class, 'album'])->name('gallery.show');
    Route::get('/announcements', [SiteController::class, 'announcements'])->name('announcements');
    Route::get('/faq', [SiteController::class, 'faq'])->name('faq');
    Route::get('/donate', [SiteController::class, 'donate'])->name('donate');

    Route::get('/contact', [ContactController::class, 'show'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::get('/register/{batch}', [RegistrationController::class, 'create'])->whereNumber('batch')->name('register.batch');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:registration')->name('register.store');
    Route::get('/register/thanks/{registration:ulid}', [RegistrationController::class, 'thanks'])->name('register.thanks');

    Route::get('/p/{page:slug}', [SiteController::class, 'page'])->name('page');
});

Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(in_array($locale, SetLocale::SUPPORTED, true), 404);
    session(['locale' => $locale]);

    return redirect()->back(fallback: '/');
})->name('locale');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Private receipts — signed URL + authorized user only.
Route::get('/media/private/{media}', PrivateMediaController::class)
    ->middleware(['auth', 'signed'])
    ->name('media.private');

// Telegram webhook (secret header checked in the controller).
Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->middleware('throttle:600,1')
    ->name('telegram.webhook');
