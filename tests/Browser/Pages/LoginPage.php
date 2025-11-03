<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class LoginPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/login';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for the page.
     */
    public function elements(): array
    {
        return [
            '@email' => 'input[name=email]',
            '@send-otp' => 'button[type=submit]:contains("Send OTP")',
            '@otp' => 'input[name=otp]',
            '@login' => 'button[type=submit]:contains("Login")',
        ];
    }

    /**
     * Enter email and send OTP.
     */
    public function sendOtp(Browser $browser, string $email): void
    {
        $browser->type('@email', $email)
                ->press('@send-otp');
    }

    /**
     * Enter OTP and login.
     */
    public function loginWithOtp(Browser $browser, string $otp): void
    {
        $browser->type('@otp', $otp)
                ->press('@login');
    }
}