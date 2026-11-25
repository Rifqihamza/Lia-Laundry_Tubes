<?php

use Illuminate\Support\Facades\Route;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

Route::get('/', function () {
    return view('welcome');
});

// Route publik Struk PDF Digital (Pengganti nota kertas lembar ke-1)
Route::get('/nota/{order}/pdf', function (Order $order) {
    $order->load(['customer', 'service']);
    $pdf = Pdf::loadView('pdf.receipt', compact('order'))
        ->setPaper('a6', 'portrait');

    return $pdf->stream("Nota-{$order->invoice_code}.pdf");
})->name('nota.pdf');
