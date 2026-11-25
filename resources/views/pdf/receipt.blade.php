<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota {{ $order->invoice_code }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 13px;
            color: #111827;
            margin: 0;
            padding: 16px;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #374151;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0 0 4px 0;
            letter-spacing: 1px;
        }
        .header p {
            margin: 0;
            font-size: 11px;
            color: #4B5563;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-weight: bold;
            font-size: 12px;
            border-radius: 4px;
            margin-top: 8px;
        }
        .paid { background-color: #DCFCE7; color: #166534; }
        .unpaid { background-color: #FEE2E2; color: #991B1B; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        td {
            padding: 5px 0;
            vertical-align: top;
        }
        .label { color: #4B5563; width: 42%; }
        .value { font-weight: bold; text-align: right; }
        .total-box {
            border-top: 2px dashed #374151;
            border-bottom: 2px dashed #374151;
            padding: 10px 0;
            margin: 10px 0;
        }
        .total-label { font-size: 14px; font-weight: bold; }
        .total-amount { font-size: 18px; font-weight: bold; text-align: right; }
        .footer {
            text-align: center;
            font-size: 11px;
            color: #4B5563;
            margin-top: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LIA LAUNDRY</h1>
        <p>Nota Digital Sah (Pengganti Nota Kertas)</p>
        <div class="badge {{ $order->payment_status === 'paid' ? 'paid' : 'unpaid' }}">
            {{ $order->payment_status === 'paid' ? 'STATUS: LUNAS' : 'STATUS: BAYAR SAAT AMBIL' }}
        </div>
    </div>

    <table>
        <tr>
            <td class="label">No. Nota</td>
            <td class="value">{{ $order->invoice_code }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Masuk</td>
            <td class="value">{{ $order->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Pelanggan</td>
            <td class="value">{{ $order->customer->name }}</td>
        </tr>
        <tr>
            <td class="label">No. WhatsApp</td>
            <td class="value">{{ $order->customer->phone }}</td>
        </tr>
        <tr>
            <td class="label">Paket Layanan</td>
            <td class="value">{{ $order->service->service_name }}</td>
        </tr>
        <tr>
            <td class="label">Berat Timbangan</td>
            <td class="value">{{ number_format((float) $order->weight_kg, 2) }} Kg</td>
        </tr>
        <tr>
            <td class="label">Estimasi Selesai</td>
            <td class="value">{{ $order->created_at->addDays($order->service->estimated_days)->format('d/m/Y') }} ({{ $order->service->estimated_days }} Hari)</td>
        </tr>
    </table>

    <div class="total-box">
        <table>
            <tr>
                <td class="total-label">TOTAL BIAYA</td>
                <td class="total-amount">Rp {{ number_format((float) $order->total_price, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Metode Bayar</td>
                <td class="value">{{ strtoupper($order->payment_method) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Simpan nota digital ini untuk ditunjukkan saat pengambilan pakaian.</p>
        <p>ID Transaksi: {{ $order->id }}</p>
    </div>
</body>
</html>