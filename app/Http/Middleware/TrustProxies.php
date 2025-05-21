<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustProxies
{

     /**
     * The trusted proxies for this application.
     *
     * @var array|string|null
     */
    protected $proxies = '*'; // Trust all proxies
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->proxies === '*') {
            $request->setTrustedProxies(
                ['127.0.0.1', $request->server->get('REMOTE_ADDR')],
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
            );
        } elseif (is_array($this->proxies)) {
            $request->setTrustedProxies(
                $this->proxies,
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
            );
        }

        return $next($request);
    }
}
