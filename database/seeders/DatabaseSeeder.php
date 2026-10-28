<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['sku' => 'NG-1001', 'name' => 'Aurora Wireless Headphones', 'category' => 'Audio', 'unit_price' => 89.00, 'stock_quantity' => 42],
            ['sku' => 'NG-1002', 'name' => 'Orbit Mechanical Keyboard', 'category' => 'Accessories', 'unit_price' => 129.00, 'stock_quantity' => 18],
            ['sku' => 'NG-1003', 'name' => 'Pixel USB-C Hub', 'category' => 'Accessories', 'unit_price' => 54.00, 'stock_quantity' => 76],
            ['sku' => 'NG-1004', 'name' => 'Summit 4K Monitor', 'category' => 'Displays', 'unit_price' => 429.00, 'stock_quantity' => 11],
            ['sku' => 'NG-1005', 'name' => 'Drift Wireless Mouse', 'category' => 'Accessories', 'unit_price' => 39.00, 'stock_quantity' => 64],
            ['sku' => 'NG-1006', 'name' => 'Echo Portable Speaker', 'category' => 'Audio', 'unit_price' => 119.00, 'stock_quantity' => 27],
            ['sku' => 'NG-1007', 'name' => 'Atlas Laptop Stand', 'category' => 'Workspace', 'unit_price' => 72.00, 'stock_quantity' => 33],
            ['sku' => 'NG-1008', 'name' => 'Nova Webcam Pro', 'category' => 'Video', 'unit_price' => 149.00, 'stock_quantity' => 8],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(['sku' => $product['sku']], $product + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $orders = [
            ['order_number' => 'NG-2401', 'ordered_at' => '2026-10-10', 'customer_name' => 'Maple Street Studio', 'status' => 'shipped'],
            ['order_number' => 'NG-2402', 'ordered_at' => '2026-10-12', 'customer_name' => 'Cedar & Co.', 'status' => 'processing'],
            ['order_number' => 'NG-2403', 'ordered_at' => '2026-10-14', 'customer_name' => 'Brightside Design', 'status' => 'delivered'],
            ['order_number' => 'NG-2404', 'ordered_at' => '2026-10-18', 'customer_name' => 'North Loop Labs', 'status' => 'shipped'],
            ['order_number' => 'NG-2405', 'ordered_at' => '2026-10-21', 'customer_name' => 'Juniper Creative', 'status' => 'processing'],
        ];

        foreach ($orders as $order) {
            DB::table('orders')->updateOrInsert(['order_number' => $order['order_number']], $order + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $orderIds = DB::table('orders')->pluck('id', 'order_number');
        $productIds = DB::table('products')->pluck('id', 'sku');
        $items = [
            ['order' => 'NG-2401', 'sku' => 'NG-1001', 'quantity' => 2],
            ['order' => 'NG-2401', 'sku' => 'NG-1005', 'quantity' => 3],
            ['order' => 'NG-2402', 'sku' => 'NG-1004', 'quantity' => 1],
            ['order' => 'NG-2402', 'sku' => 'NG-1003', 'quantity' => 2],
            ['order' => 'NG-2403', 'sku' => 'NG-1002', 'quantity' => 1],
            ['order' => 'NG-2403', 'sku' => 'NG-1006', 'quantity' => 2],
            ['order' => 'NG-2404', 'sku' => 'NG-1008', 'quantity' => 1],
            ['order' => 'NG-2404', 'sku' => 'NG-1007', 'quantity' => 2],
            ['order' => 'NG-2405', 'sku' => 'NG-1001', 'quantity' => 1],
            ['order' => 'NG-2405', 'sku' => 'NG-1003', 'quantity' => 4],
        ];

        foreach ($items as $item) {
            $product = DB::table('products')->where('sku', $item['sku'])->first();
            DB::table('order_items')->updateOrInsert([
                'order_id' => $orderIds[$item['order']],
                'product_id' => $productIds[$item['sku']],
            ], [
                'quantity' => $item['quantity'],
                'unit_price' => $product->unit_price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
