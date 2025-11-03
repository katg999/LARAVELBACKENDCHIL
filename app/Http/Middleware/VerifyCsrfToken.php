<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'marzpay/webhook',
        // 'admin/durations' // Re-enabled CSRF protection for admin/durations routes
    ];

    /**
     * Override to safely add XSRF cookie when response may be a View.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $response
     * @return mixed
     */
    protected function addCookieToResponse($request, $response)
    {
        $config = config('session');

        // If the response implements Responsable, convert it first
        if ($response instanceof \Illuminate\Contracts\Support\Responsable) {
            $response = $response->toResponse($request);
        }

        // If a View instance was returned, convert to a Response
        if ($response instanceof \Illuminate\View\View) {
            $response = response($response);
        }

        // If headers are not available, bail out gracefully
        if (! isset($response->headers)) {
            return $response;
        }

        $response->headers->setCookie($this->newCookie($request, $config));

        return $response;
    }
}
