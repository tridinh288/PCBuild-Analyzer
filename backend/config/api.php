<?php

return [

    /*
    | Requests per minute (spec section 32). Builder, analysis and compare endpoints run
    | the engine and are limited more strictly than plain reads.
    */
    'rate_limits' => [
        'public' => (int) env('API_RATE_LIMIT_PUBLIC', 60),
        'analysis' => (int) env('API_RATE_LIMIT_ANALYSIS', 30),
        'login' => (int) env('API_RATE_LIMIT_LOGIN', 5),
    ],

];
