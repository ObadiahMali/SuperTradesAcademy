<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
   public function run()
{
    $plans = config('plans.plans', []);

    foreach ($plans as $key => $meta) {
        \App\Models\Plan::updateOrCreate(
            ['key' => $key],
            [
                'label' => $meta['label'] ?? $key,
                'price' => $meta['price'] ?? 0,
                'currency' => $meta['currency'] ?? 'USD',
            ]
        );
    }
}
}