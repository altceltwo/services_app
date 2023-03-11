<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/registro',
        '/login',
        '/login-pos',
        '/get-rates-pos',
        '/save-recharge-pos',
        'get-data-user-pos',
        '/addDevice',
        '/get-number-by-icc',
        '/get-rates-activation-pos',
        '/save-data-activation-pos',
        '/updateClient'
    ];
}