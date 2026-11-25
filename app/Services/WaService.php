<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WaLogs;
use Illuminate\Support\Facades\Http;

class WaService
{
    /**
     * Mengirim struk nota awal (pengganti nota kertas lembar ke-1)
     */
    public function sendReceipt(Order $order): WaLogs
    {
        $notaUrl = url("/nota/{$order->id}");
        $statusBayar = $order->payment_status === 'paid' ? 'LUNAS' : 'BAYAR SAAT AMBIL';

        $message = "*LIA LAUNDRY — NOTA DIGITAL*\n"
            . "No. Nota: *{$order->invoice_code}*\n"
            . "Nama: {$order->customer->name}\n"
            . "Layanan: {$order->service->service_name} ({$order->weight_kg} Kg)\n"
            . "Total: *Rp " . number_format((float) $order->total_price, 0, ',', '.') . "* ({$statusBayar})\n\n"
            . "Lihat struk PDF & pantau status cucian di sini:\n{$notaUrl}";

        return $this->dispatchMessage($order, 'receipt', $message);
    }

    /**
     * Mengirim notifikasi otomatis saat karyawan menekan 'Cucian Selesai'
     */
    public function sendCompletedNotification(Order $order): WaLogs
    {
        $notaUrl = url("/nota/{$order->id}");
        $tagihanInfo = $order->payment_status === 'unpaid'
            ? "Total tagihan yang perlu dibayar saat ambil: *Rp " . number_format((float) $order->total_price, 0, ',', '.') . "*.\n"
            : "Status pembayaran: *LUNAS*.\n";

        $message = "*LIA LAUNDRY — CUCIAN SELESAI*\n"
            . "Halo Kak *{$order->customer->name}*, cucian dengan nomor nota *{$order->invoice_code}* sudah selesai dicuci & disetrika.\n"
            . $tagihanInfo . "\n"
            . "Silakan tunjukkan nota digital berikut saat pengambilan:\n{$notaUrl}";

        return $this->dispatchMessage($order, 'completed_notif', $message);
    }

    /**
     * Eksekutor pengiriman pesan dengan mekanisme Anti-Stuck (try-catch)
     */
    private function dispatchMessage(Order $order, string $type, string $message): WaLogs
    {
        $phone = $order->customer->phone;
        $token = env('WA_API_TOKEN'); // Kosongkan saat demo offline/lokal

        try {
            if (!empty($token)) {
                // Contoh integrasi ke Fonnte / WA Gateway sejenis
                $response = Http::withHeaders([
                    'Authorization' => $token,
                ])->timeout(5)->post('https://api.fonnte.com/send', [
                    'target' => $phone,
                    'message' => $message,
                ]);

                if (!$response->successful()) {
                    throw new \Exception('Gateway merespons gagal: ' . $response->status());
                }
            }

            // Jika token kosong (mode demo tugas besar) atau API sukses:
            return WaLogs::create([
                'order_id' => $order->id,
                'message_type' => $type,
                'recipient_phone' => $phone,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Transaksi tetap aman, kegagalan dicatat agar kasir bisa klik 'Kirim Ulang WA'
            return WaLogs::create([
                'order_id' => $order->id,
                'message_type' => $type,
                'recipient_phone' => $phone,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
            ]);
        }
    }
}
