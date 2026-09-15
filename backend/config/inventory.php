<?php

return [
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 5),
    'currency' => env('STORE_CURRENCY', 'USD'),
    'currency_symbol' => env('STORE_CURRENCY_SYMBOL', '$'),
];
