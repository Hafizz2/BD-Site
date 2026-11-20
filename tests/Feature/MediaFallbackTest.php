<?php

use App\Models\Program;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('uses the resized image when it exists', function () {
    Storage::fake('public');
    $program = Program::factory()->create();
    $program->addMedia(UploadedFile::fake()->image('cover.jpg', 1200, 800))->toMediaCollection('cover');

    $media = $program->getFirstMedia('cover');
    expect($media->hasGeneratedConversion('card'))->toBeTrue()
        ->and($program->getFirstMediaUrl('cover', 'card'))->toContain('/conversions/')->toContain('-card.jpg');
});

it('falls back to the original image when the resized copy was never made (no broken pictures)', function () {
    Storage::fake('public');
    $program = Program::factory()->create(['show_on_website' => true, 'is_active' => true]);
    $program->addMedia(UploadedFile::fake()->image('cover.jpg', 1200, 800))->toMediaCollection('cover');

    // Simulate the live server: upload worked but the "card" copy was never generated.
    $media = $program->getFirstMedia('cover');
    $media->generated_conversions = [];
    $media->save();

    $url = $program->fresh()->getFirstMediaUrl('cover', 'card');
    expect($url)->not->toContain('/conversions/')->toEndWith('cover.jpg');

    // The public programs page now links the original instead of a missing file.
    $this->get('/programs')->assertOk()->assertSee($url, false);
});

it('saves new website images on the public disk', function () {
    Storage::fake('public');
    $program = Program::factory()->create();
    $program->addMedia(UploadedFile::fake()->image('cover.jpg', 800, 500))->toMediaCollection('cover');

    expect($program->getFirstMedia('cover')->disk)->toBe('public');
});

it('moves website images that were saved privately to the public disk (and leaves receipts private)', function () {
    Storage::fake('public');
    Storage::fake('local');
    Storage::fake('private');

    // Like the live server: a cover image ended up on the private "local" disk.
    $program = Program::factory()->create(['show_on_website' => true, 'is_active' => true]);
    $media = $program->addMedia(UploadedFile::fake()->image('cover.jpg', 1200, 800))->toMediaCollection('cover', 'local');
    expect($media->disk)->toBe('local');
    Storage::disk('local')->assertExists($media->id.'/cover.jpg');

    // A payment receipt must stay private.
    $payment = \App\Models\Payment::factory()->create();
    $receipt = $payment->addMedia(UploadedFile::fake()->image('receipt.jpg'))->toMediaCollection('receipt');

    $this->artisan('media:make-website-public')->assertSuccessful();

    $media->refresh();
    expect($media->disk)->toBe('public')->and($media->conversions_disk)->toBe('public');
    Storage::disk('public')->assertExists($media->id.'/cover.jpg');
    Storage::disk('public')->assertExists($media->id.'/conversions/cover-card.jpg');
    Storage::disk('local')->assertMissing($media->id.'/cover.jpg');
    expect($receipt->fresh()->disk)->toBe('private');

    $this->get('/programs')->assertOk()->assertSee($program->fresh()->getFirstMediaUrl('cover', 'card'), false);

    // Running it again changes nothing.
    $this->artisan('media:make-website-public')->assertSuccessful()->expectsOutputToContain('already public');
});
