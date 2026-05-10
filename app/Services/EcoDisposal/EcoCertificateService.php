<?php

namespace App\Services\EcoDisposal;

use App\Models\DisposalItem;
use App\Models\EcoCertificate;
use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class EcoCertificateService
{
    // TODO: move to config/eco.php
    private const CO2_KG_PER_KG_WEIGHT = 0.5; // грубая оценка, подлежит уточнению

    public function issueForOrder(Order $order): EcoCertificate
    {
        if (! $order->isEcoDisposal()) {
            throw new \InvalidArgumentException('EcoCertificate доступен только для ECO_DISPOSAL заказов');
        }
        if ($order->status !== 'completed') {
            throw new \InvalidArgumentException('EcoCertificate можно выпустить только для завершенных заказов');
        }

        $existing = EcoCertificate::where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $order->loadMissing('disposalDetails', 'user');
        $details = $order->disposalDetails;
        if (! $details) {
            throw new \RuntimeException('Отсутствуют DisposalOrderDetails для заказа');
        }

        $itemsInput = is_array($details->items) ? $details->items : [];
        $itemIds = collect($itemsInput)->pluck('disposal_item_id')->filter()->values()->all();
        $items = DisposalItem::query()->whereIn('id', $itemIds)->get()->keyBy('id');

        $summaryItems = [];
        $totalVolume = 0.0;
        $totalWeight = 0.0;
        $reusedCount = 0;
        foreach ($itemsInput as $row) {
            $id = (int) ($row['disposal_item_id'] ?? 0);
            $qty = (int) ($row['quantity'] ?? 1);
            $model = $items->get($id);
            if (! $model) {
                continue;
            }
            $rowVolume = (float) ($model->volume_m3 ?? 0) * $qty;
            $rowWeight = (float) ($model->weight_kg ?? 0) * $qty;
            $totalVolume += $rowVolume;
            $totalWeight += $rowWeight;
            if (($model->disposal_path ?? null) === 'DONATABLE') {
                $reusedCount += $qty;
            }
            $summaryItems[] = [
                'disposal_item_id' => $model->id,
                'name' => $model->name,
                'category' => $model->category,
                'disposal_path' => $model->disposal_path,
                'quantity' => $qty,
                'volume_m3' => (float) ($model->volume_m3 ?? 0),
                'weight_kg' => (float) ($model->weight_kg ?? 0),
            ];
        }

        $summary = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->user?->name ?? '—',
            'address' => Arr::get($order->address?->toArray() ?? [], 'line_1') ?? ($order->metadata['address_line'] ?? null),
            'city' => Arr::get($order->address?->toArray() ?? [], 'city') ?? ($order->metadata['city'] ?? null),
            'performed_at' => optional($order->completed_at)->toIso8601String(),
            'items' => $summaryItems,
            'totals' => [
                'total_volume_m3' => round($totalVolume, 3),
                'total_weight_kg' => round($totalWeight, 3),
            ],
            'partner' => $details->ecoPartner ? [
                'id' => $details->ecoPartner->id,
                'name' => $details->ecoPartner->name,
                'type' => $details->ecoPartner->type,
            ] : null,
            'team' => $details->ecoTeam ? [
                'id' => $details->ecoTeam->id,
                'name' => $details->ecoTeam->name,
            ] : null,
        ];

        $co2SavedKg = round($totalWeight * self::CO2_KG_PER_KG_WEIGHT, 3);

        $certificate = EcoCertificate::create([
            'order_id' => $order->id,
            'certificate_uid' => (string) Str::uuid(),
            'customer_name' => $summary['customer_name'],
            'summary_data' => $summary,
            'co2_saved_kg' => $co2SavedKg,
            'items_reused_count' => $reusedCount,
            'issued_at' => now(),
            'pdf_path' => null,
        ]);

        try {
            $pdfPath = $this->generatePdf($certificate);
            if ($pdfPath) {
                $certificate->pdf_path = $pdfPath;
                $certificate->save();
            }
        } catch (\Throwable $e) {
            Log::error('EcoCertificate PDF generation failed', ['order_id' => $order->id, 'err' => $e->getMessage()]);
        }

        return $certificate;
    }

    protected function generatePdf(EcoCertificate $certificate): ?string
    {
        $data = [
            'certificate' => $certificate->fresh('order'),
            'summary' => $certificate->summary_data ?? [],
        ];

        if (! View::exists('pdf.eco_certificate')) {
            Log::warning('PDF view pdf.eco_certificate not found, skipping PDF generation');

            return null;
        }

        // Prefer barryvdh/dompdf if installed
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.eco_certificate', $data);
            $year = now()->format('Y');
            $relPath = "eco-certificates/{$year}/{$certificate->certificate_uid}.pdf";
            Storage::put($relPath, $pdf->output());

            return $relPath;
        }

        // Fallback: store HTML snapshot if no PDF library available
        $html = view('pdf.eco_certificate', $data)->render();
        $year = now()->format('Y');
        $relPath = "eco-certificates/{$year}/{$certificate->certificate_uid}.html";
        Storage::put($relPath, $html);

        return $relPath;
    }
}
