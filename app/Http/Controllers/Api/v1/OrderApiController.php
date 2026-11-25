<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\WaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\LaundryService;

class OrderApiController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private WaService $waService
    ) {}

    public function searchCustomers(Request $request): JsonResponse
    {
        $keyword = $request->query('q', '');

        $customers = Customer::where('name', 'like', "%{$keyword}%")
            ->orWhere('phone', 'like', "%{$keyword}%")
            ->limit(10)
            ->get(['id', 'name', 'phone']);

        return response()->json([
            'status' => 'success',
            'data' => $customers,
        ]);
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|uuid|exists:laundry_services,id',
            'weight_kg' => 'required|numeric|min:0.1|max:200',
        ]);

        $result = $this->orderService->calculatePrice(
            $validated['service_id'],
            (float) $validated['weight_kg']
        );

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'customer_name' => 'required_without:customer_id|string|max:100',
            'customer_phone' => 'required_without:customer_id|string|max:20',
            'service_id' => 'required|uuid|exists:laundry_services,id',
            'weight_kg' => 'required|numeric|min:0.1|max:200',
            'payment_method' => 'required|in:cash,qris,pending',
            'payment_status' => 'required|in:unpaid,paid',
            'confirmed_by' => 'nullable|uuid|exists:users,id',
        ]);

        if (empty($validated['customer_id'])) {
            $phone = preg_replace('/^0/', '62', trim($validated['customer_phone']));
            $customer = Customer::firstOrCreate(
                ['phone' => $phone],
                ['name' => $validated['customer_name']]
            );
            $customerId = $customer->id;
        } else {
            $customerId = $validated['customer_id'];
        }

        $calc = $this->orderService->calculatePrice(
            $validated['service_id'],
            (float) $validated['weight_kg']
        );

        $kasirId = $validated['confirmed_by']
            ?? $request->user()?->id
            ?? User::where('role', 'kasir')->firstOrFail()->id;

        $order = Order::create([
            'invoice_code' => $this->orderService->generateInvoiceCode(),
            'customer_id' => $customerId,
            'service_id' => $validated['service_id'],
            'weight_kg' => $validated['weight_kg'],
            'total_price' => $calc['total_price'],
            'payment_method' => $validated['payment_method'],
            'payment_status' => $validated['payment_status'],
            'laundry_status' => 'queued',
            'confirmed_by' => $kasirId,
        ]);

        $order->load(['customer', 'service']);

        $waLog = $this->waService->sendReceipt($order);

        return response()->json([
            'status' => 'success',
            'message' => 'Order berhasil dicatat dan nota dikirim ke WhatsApp.',
            'pdf_url' => url("/nota/{$order->id}/pdf"),
            'wa_status' => $waLog->status,
            'data' => $order,
        ], 201);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'laundry_status' => 'required|in:queued,processing,completed,picked_up',
            'payment_status' => 'nullable|in:unpaid,paid',
            'payment_method' => 'nullable|in:cash,qris,pending',
            'processed_by' => 'nullable|uuid|exists:users,id',
        ]);

        $order->laundry_status = $validated['laundry_status'];

        if (!empty($validated['payment_status'])) {
            $order->payment_status = $validated['payment_status'];
        }
        if (!empty($validated['payment_method'])) {
            $order->payment_method = $validated['payment_method'];
        }
        if (!empty($validated['processed_by'])) {
            $order->processed_by = $validated['processed_by'];
        }

        if ($validated['laundry_status'] === 'completed') {
            $order->completed_at = now();
        }
        if ($validated['laundry_status'] === 'picked_up') {
            $order->picked_up_at = now();
        }

        $order->save();
        $order->load(['customer', 'service']);

        $waStatus = null;
        if ($validated['laundry_status'] === 'completed') {
            $waLog = $this->waService->sendCompletedNotification($order);
            $waStatus = $waLog->status;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status cucian berhasil diperbarui.',
            'wa_status' => $waStatus,
            'data' => $order,
        ]);
    }

    public function trackStatus(Order $order): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'invoice_code' => $order->invoice_code,
                'customer_name' => $order->customer->name,
                'service_name' => $order->service->service_name,
                'weight_kg' => $order->weight_kg,
                'total_price' => $order->total_price,
                'payment_status' => $order->payment_status,
                'laundry_status' => $order->laundry_status,
                'pdf_url' => url("/nota/{$order->id}/pdf"),
                'updated_at' => $order->updated_at->toDateTimeString(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['customer', 'service', 'waLogs'])->latest();

        if ($request->filled('laundry_status')) {
            $query->where('laundry_status', $request->laundry_status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('invoice_code', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', function ($sub) use ($keyword) {
                        $sub->where('name', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    });
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->limit(50)->get(),
        ]);
    }

    public function resendWa(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'message_type' => 'nullable|in:receipt,completed_notif',
        ]);

        $order->load(['customer', 'service']);

        $type = $validated['message_type']
            ?? ($order->laundry_status === 'completed' ? 'completed_notif' : 'receipt');

        $waLog = $type === 'completed_notif'
            ? $this->waService->sendCompletedNotification($order)
            : $this->waService->sendReceipt($order);

        return response()->json([
            'status' => $waLog->status === 'sent' ? 'success' : 'error',
            'message' => $waLog->status === 'sent'
                ? 'Pesan WhatsApp berhasil dikirim ulang.'
                : 'Gagal mengirim ulang WhatsApp: ' . $waLog->error_message,
            'wa_log' => $waLog,
        ], $waLog->status === 'sent' ? 200 : 502);
    }

    public function services(): JsonResponse
    {
        $services = LaundryService::where('is_active', true)
            ->orderBy('service_name')
            ->get(['id', 'service_name', 'price_per_kg', 'estimated_days']);

        return response()->json([
            'status' => 'success',
            'data' => $services,
        ]);
    }

    public function dashboardSummary(): JsonResponse
    {
        $today = now()->toDateString();
        $todayOrders = Order::whereDate('created_at', $today);

        return response()->json([
            'status' => 'success',
            'data' => [
                'today_omzet_paid' => (float) (clone $todayOrders)->where('payment_status', 'paid')->sum('total_price'),
                'today_cash' => (float) (clone $todayOrders)->where('payment_status', 'paid')->where('payment_method', 'cash')->sum('total_price'),
                'today_qris' => (float) (clone $todayOrders)->where('payment_status', 'paid')->where('payment_method', 'qris')->sum('total_price'),
                'total_unpaid_tagihan' => (float) Order::where('payment_status', 'unpaid')->sum('total_price'),
                'count_queued' => Order::where('laundry_status', 'queued')->count(),
                'count_processing' => Order::where('laundry_status', 'processing')->count(),
                'count_completed' => Order::where('laundry_status', 'completed')->count(),
            ],
        ]);
    }
}
