<?php
declare(strict_types=1);

return [
    'currency' => 'USD',
    'provider_mode' => env('FINANCIAL_PROVIDER_MODE', 'manual'),
    'transfer' => [
        'minimum_cents' => 100,
        'maximum_cents' => 500000,
        'daily_limit_cents' => 1000000,
        'fee_flat_cents' => 50,
        'step_up_cents' => (int) env('TRANSFER_STEP_UP_CENTS', '0'),
    ],
    'funding' => [
        'minimum_cents' => 500,
        'maximum_cents' => 500000,
        'daily_limit_cents' => 1000000,
        'fee_flat_cents' => 30,
        'fee_basis_points' => 150,
    ],
    'credit' => [
        'minimum_cents' => 10000,
        'maximum_cents' => 500000,
        'annual_rate_basis_points' => 1200,
        'allowed_terms' => [3, 6, 12],
        'minimum_account_age_days' => 30,
        'minimum_completed_transactions' => 3,
    ],
];
