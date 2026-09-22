<?php

/*
| CORS: only the React app may call the API from a browser (spec section 32).
| Admin auth uses bearer tokens, not cookies (D-023), so credentials are not needed.
*/

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter([env('FRONTEND_URL', 'http://localhost:5173')]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Accept', 'Authorization', 'X-Requested-With'],

    'exposed_headers' => ['Retry-After'],

    'max_age' => 3600,

    'supports_credentials' => false,

];
