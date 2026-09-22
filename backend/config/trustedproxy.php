<?php

/*
|--------------------------------------------------------------------------
| Trusted proxies — read by Laravel's TrustProxies middleware
|--------------------------------------------------------------------------
|
| Measured on Render (Phase 8): a request arrives as
|
|   X-Forwarded-For: <client>, <Cloudflare edge>, <Render internal 10.x>   REMOTE_ADDR: 127.0.0.1
|
| The client IP is the rightmost address that is NOT a trusted proxy, so every hop the platform
| adds must be listed here; otherwise the Cloudflare edge IP is taken as the client and rate
| limits are split per edge server. Never use '*': in Laravel it trusts every address, which
| lets a client spoof its IP with its own X-Forwarded-For header.
|
| Cloudflare ranges: https://www.cloudflare.com/ips (fetched 2026-09-22).
|
*/

return [

    'proxies' => [
        'REMOTE_ADDR',

        // Private networks: the platform's internal hops
        '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16',

        // Cloudflare (in front of Render)
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
        '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
        '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32',
    ],

];
