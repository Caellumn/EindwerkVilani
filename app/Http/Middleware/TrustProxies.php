<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * Trust all proxies (or list your DO App Platform IPs/CIDRs).
     *
     * @var array|string|null
     */
    protected $proxies = '*';

    /**
     * Which forwarded headers to trust.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR   |
        Request::HEADER_X_FORWARDED_HOST  |
        Request::HEADER_X_FORWARDED_PORT  |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
