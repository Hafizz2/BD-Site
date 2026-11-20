<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves receipts from the private disk. Requires a valid signed URL AND an authorized user.
 */
class PrivateMediaController extends Controller
{
    public function __invoke(Request $request, string $media): StreamedResponse
    {
        $media = Media::where('uuid', $media)->firstOrFail();
        $owner = $media->model;
        $user = $request->user();

        $allowed = match (true) {
            $owner instanceof Payment, $owner instanceof Expense => $user?->can('view', $owner),
            default => $user?->isSuperAdmin(),
        };
        abort_unless($allowed, 403);

        return response()->stream(function () use ($media) {
            $stream = $media->stream();
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($media->file_name).'"',
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
