<?php

use App\Models\Batch;
use Database\Seeders\WebsiteContentSeeder;

beforeEach(function () {
    $this->seed(WebsiteContentSeeder::class);
    Batch::factory()->create();
});

it('renders every public page', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    '/', '/about', '/goals', '/services', '/programs', '/articles', '/events', '/gallery',
    '/announcements', '/faq', '/donate', '/contact', '/register', '/sitemap.xml',
]);

it('switches language to Amharic', function () {
    $this->get('/lang/am')->assertRedirect();
    $this->get('/')->assertOk();
    expect(app()->getLocale())->toBe('am');
});

it('returns 404 for an unknown language', function () {
    $this->get('/lang/fr')->assertNotFound();
});

it('never shows jemea members on the public site', function () {
    $m = \App\Models\Member::factory()->create(['full_name' => 'Secret Person', 'show_on_website' => true, 'is_active' => true]);

    $this->get('/team')->assertNotFound();
    foreach (['/', '/about', '/sitemap.xml'] as $url) {
        $this->get($url)->assertOk()->assertDontSee('Secret Person')->assertDontSee('/team');
    }
});

it('shows friendly error pages', function () {
    $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('Page not found')->assertSee('ገጹ አልተገኘም');

    // Signed in, but without permission for an admin page → the 403 page with a way back to the dashboard.
    $this->seed(\Database\Seeders\RolesSeeder::class);
    $media = \App\Models\User::factory()->create(['is_active' => true]);
    $media->assignRole('media');
    $this->actingAs($media)->get(\App\Filament\Resources\PaymentResource::getUrl('index'))
        ->assertForbidden()->assertSee('You don&#039;t have access', false)->assertSee('Back to the dashboard');
});

it('renders every error page without touching the database', function (int $code) {
    \Illuminate\Support\Facades\DB::flushQueryLog();
    \Illuminate\Support\Facades\DB::enableQueryLog();

    expect(view("errors.$code")->render())->toContain((string) $code)->toContain('Ye Birhan Dana')
        ->and(\Illuminate\Support\Facades\DB::getQueryLog())->toBe([]);
})->with([401, 403, 404, 419, 429, 500, 503]);
