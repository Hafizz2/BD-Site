<?php

use App\Models\Batch;
use App\Models\Registration;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;

beforeEach(function () {
    config()->set('app.admin', ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'secret-password']);
    $this->seed(RolesSeeder::class);
    $this->actingAs(User::where('email', 'boss@example.com')->sole());
    Registration::factory()->count(2)->create(['batch_id' => Batch::factory()]);
});

it('opens every admin list page without errors', function () {
    $failed = [];
    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        if (! $resource::hasPage('index')) {
            continue;
        }
        $response = $this->get($resource::getUrl('index'));
        if ($response->status() !== 200) {
            $failed[class_basename($resource)] = $response->exception?->getMessage() ?? $response->status();
        }
    }

    expect($failed)->toBe([]);
});

it('opens every create page and custom page without errors', function () {
    $panel = Filament::getPanel('admin');
    $urls = [];
    foreach ($panel->getResources() as $resource) {
        if ($resource::hasPage('create')) {
            $urls[class_basename($resource).' create'] = $resource::getUrl('create');
        }
    }
    foreach ($panel->getPages() as $page) {
        $urls[class_basename($page)] = $page::getUrl();
    }

    $failed = [];
    foreach ($urls as $name => $url) {
        $response = $this->get($url);
        if ($response->status() !== 200) {
            $failed[$name] = mb_substr($response->exception?->getMessage() ?? (string) $response->status(), 0, 200);
        }
    }

    expect($failed)->toBe([]);
});

it('opens the import students page', function () {
    $this->get(\App\Filament\Pages\ImportStudents::getUrl())->assertOk();
});

it('opens a registration view page', function () {
    $this->get(\App\Filament\Resources\RegistrationResource::getUrl('view', ['record' => Registration::first()]))->assertOk();
});
