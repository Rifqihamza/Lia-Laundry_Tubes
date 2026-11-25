<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\LaundryService;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Bu Lia (Pemilik)',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('adminlaundry5842'),
            'role' => 'admin',
            'phone' => '6281211110001',
        ]);

        $kasir = User::create([
            'name' => 'Mbak Sari (Kasir)',
            'email' => 'cashier@gmail.com',
            'password' => Hash::make('sarikasir123'),
            'role' => 'cashier',
            'phone' => '6281211110002',
        ]);

        $karyawan = User::create([
            'name' => 'Mas Budi (Cuci)',
            'email' => 'employee@gmail.com',
            'password' => Hash::make('budikaryawan123'),
            'role' => 'employee',
            'phone' => '6281211110003',
        ]);

        $reguler = LaundryService::create([
            'service_name' => 'Cuci Setrika Reguler',
            'price_per_kg' => 7000,
            'estimated_days' => 3,
        ]);

        LaundryService::create([
            'service_name' => 'Cuci Kering Lipat',
            'price_per_kg' => 5000,
            'estimated_days' => 2,
        ]);

        LaundryService::create([
            'service_name' => 'Cuci Setrika Kilat (1 Hari)',
            'price_per_kg' => 12000,
            'estimated_days' => 1,
        ]);

        $customer = Customer::create([
            'name' => 'Ibu Rina Wahyuni',
            'phone' => '6281234567890',
            'notes' => 'Pelanggan tetap komplek depan',
        ]);

        Order::create([
            'invoice_code' => 'LIA-' . now()->format('Ymd') . '-001',
            'customer_id' => $customer->id,
            'service_id' => $reguler->id,
            'weight_kg' => 4.5,
            'total_price' => 31500,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'laundry_status' => 'queued',
            'confirmed_by' => $kasir->id,
        ]);
    }
}
