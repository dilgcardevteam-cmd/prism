<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reusable page-level feature switches
    |--------------------------------------------------------------------------
    |
    | Keep pages in the codebase but hide them from users by default.
    | Re-enable later by setting the matching .env value to true.
    |
    */
    'nadai_management' => env('FEATURE_NADAI_MANAGEMENT_ENABLED', false),
    'confirmation_of_fund_receipt' => env('FEATURE_CONFIRMATION_OF_FUND_RECEIPT_ENABLED', false),
];
