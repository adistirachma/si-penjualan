<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Catatan: hanya mengisi kolom yang ada di tabel sales (product_id, user_id, quantity, sale_date).
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $user = User::create([
                'name'     => 'Admin Dony',
                'email'    => 'admin@donysperabot.com',
                'password' => Hash::make('password'),
            ]);
        }

        $products = Product::all();
        if ($products->isEmpty()) {
            return;
        }

        foreach ($products as $product) {
            // 1 record per bulan per produk (9 bulan ke belakang)
            for ($monthOffset = 8; $monthOffset >= 0; $monthOffset--) {
                $month    = Carbon::now()->subMonths($monthOffset)->startOfMonth();
                $quantity = rand(10, 80);

                Sale::create([
                    'product_id' => $product->id,
                    'user_id'    => $user->id,
                    'quantity'   => $quantity,
                    'sale_date'  => $month->toDateString(),
                ]);
            }
        }
    }
}
