<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use Illuminate\Support\Str;

class PlansTableSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            ['key' => 'demand-and-supply-book', 'label' => 'Demand and supply Book', 'price' => 29.00,  'currency' => 'USD'],
            ['key' => 'online-mentorship',        'label' => 'Online Mentorship',       'price' => 90.00,  'currency' => 'USD'],
            ['key' => 'signals_1month',          'label' => 'Signals 1 month',        'price' => 59.00,  'currency' => 'USD'],
            ['key' => 'signals_3month',          'label' => 'Signals 3 months',      'price' => 79.00,  'currency' => 'USD'],
            ['key' => 'signals_6month',          'label' => 'Signals 6 months',      'price' => 99.00,  'currency' => 'USD'],
            ['key' => 'signals_12month',         'label' => 'Signals 12 months',     'price' => 150.00, 'currency' => 'USD'],
            ['key' => 'physical-mentorship',     'label' => 'Physical Mentorship',   'price' => 150.00, 'currency' => 'USD'],
        ];

        foreach ($plans as $p) {
            $normalizedKey = Str::slug($p['key'], '-');

            Plan::updateOrCreate(
                ['key' => $normalizedKey],
                [
                    'key' => $normalizedKey,
                    'label' => $p['label'],
                    'price' => $p['price'],
                    'currency' => $p['currency'],
                    // 'active' => $p['active'] ?? true,
                ]
            );
        }
    }
}