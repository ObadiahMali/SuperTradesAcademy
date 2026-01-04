<?php

return [

    // key => [label, price (USD), currency]
    'plans' => [
        'signals_monthly_1' => ['label' => 'Signals 1 month', 'price' => 59,  'currency' => 'USD'],
        'signals_monthly_3' => ['label' => 'Signals 3 months', 'price' => 79,  'currency' => 'USD'],
        'signals_monthly_6' => ['label' => 'Signals 6 months', 'price' => 99,  'currency' => 'USD'],
        'signals_yearly'    => ['label' => 'Signals 12 months', 'price' => 150, 'currency' => 'USD'],
        'physical_mentorship' => ['label' => 'Physical Mentorship', 'price' => 150, 'currency' => 'USD'],
        'online_mentorship'   => ['label' => 'Online Mentorship',  'price' => 110, 'currency' => 'USD'],
        'book'                => ['label' => 'Book',                'price' => 30,  'currency' => 'USD'], // if you keep prior book price
    ],

];