<?php

namespace App\Services;

use App\Models\LaundryService;
use App\Models\Order;

class OrderService
{
    /**
     * Menghitung total biaya berdasarkan berat timbangan dan jenis layanan
     */
    public function calculatePrice(string $serviceId, float $weightKg): array
    {
        $service = LaundryService::findOrFail($serviceId);
        $totalPrice = round($weightKg * (float) $service->price_per_kg, 2);

        return [
            'service_name' => $service->service_name,
            'price_per_kg' => (float) $service->price_per_kg,
            'weight_kg' => $weightKg,
            'total_price' => $totalPrice,
            'formatted_total' => 'Rp ' . number_format($totalPrice, 0, ',', '.'),
        ];
    }

    /**
     * Membuat nomor nota otomatis format: LIA-YYYYMMDD-001
     */
    public function generateInvoiceCode(): string
    {
        $todayPrefix = 'LIA-' . now()->format('Ymd') . '-';
        $countToday = Order::where('invoice_code', 'like', $todayPrefix . '%')->count();
        $nextNumber = str_pad((string) ($countToday + 1), 3, '0', STR_PAD_LEFT);

        return $todayPrefix . $nextNumber;
    }
}
