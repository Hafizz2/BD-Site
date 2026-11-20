<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Settings\GeneralSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateService
{
    /** Mark an enrollment completed and give it a certificate number (idempotent). */
    public function issue(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->certificate_number) {
            return $enrollment;
        }

        $enrollment->forceFill([
            'status' => EnrollmentStatus::Completed,
            'certificate_issued_at' => now(),
            'certificate_number' => 'YBD-'.now()->format('Y').'-'.str_pad((string) $enrollment->id, 5, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
        ])->save();

        return $enrollment;
    }

    public function pdf(Enrollment $enrollment)
    {
        $enrollment = $this->issue($enrollment->loadMissing(['student', 'batch.program']));

        return Pdf::loadView('pdf.certificate', [
            'enrollment' => $enrollment,
            'site' => app(GeneralSettings::class),
            'ethiopicFont' => is_file(storage_path('fonts/NotoSansEthiopic-Regular.ttf')) ? storage_path('fonts/NotoSansEthiopic-Regular.ttf') : null,
        ])->setPaper('a4', 'landscape');
    }

    public function download(Enrollment $enrollment): StreamedResponse
    {
        $pdf = $this->pdf($enrollment);
        $name = Str::slug($enrollment->student->full_name ?: 'student').'-certificate.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $name, ['Content-Type' => 'application/pdf']);
    }
}
