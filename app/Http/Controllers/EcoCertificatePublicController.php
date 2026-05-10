<?php

namespace App\Http\Controllers;

use App\Models\EcoCertificate;

class EcoCertificatePublicController extends Controller
{
    public function show(string $uid)
    {
        $certificate = EcoCertificate::where('certificate_uid', $uid)->firstOrFail();
        $summary = $certificate->summary_data ?? [];

        return view('eco-disposal/certificate', compact('certificate', 'summary'));
    }
}
