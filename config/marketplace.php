<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform application fee
    |--------------------------------------------------------------------------
    |
    | Percent of each sale retained by the platform (Stripe Connect
    | application_fee_amount). Integer percent, e.g. 10 = 10%.
    |
    */

    'application_fee_percent' => (int) env('MARKETPLACE_APPLICATION_FEE_PERCENT', 10),

    /*
    |--------------------------------------------------------------------------
    | Upload limits
    |--------------------------------------------------------------------------
    */

    'max_upload_kb' => (int) env('MARKETPLACE_MAX_UPLOAD_KB', 512000),

    'stripe_connect_country' => env('STRIPE_CONNECT_COUNTRY', 'US'),

];
