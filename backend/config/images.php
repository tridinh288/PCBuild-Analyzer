<?php

return [

    // cloudinary://<api_key>:<api_secret>@<cloud_name> — never committed (D-022)
    'cloudinary_url' => env('CLOUDINARY_URL'),

    // Cloudinary transformation per display size (spec section 25)
    'presets' => [
        'thumb' => 'c_fill,w_400,h_300,f_auto,q_auto',
        'large' => 'c_limit,w_1000,f_auto,q_auto',
    ],

    'folders' => [
        'products' => 'pcbuild/products',
        'builds' => 'pcbuild/builds',
    ],

    'upload' => [
        'max_kb' => 2048,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

];
