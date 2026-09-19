<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Services\MarzPayService;
use Tests\TestCase;

class PaymentGatewayContractTest extends TestCase
{
    /** @test */
    public function marzpay_is_the_default_gateway(): void
    {
        $gateway = app(PaymentGateway::class);

        $this->assertInstanceOf(MarzPayService::class, $gateway);
        $this->assertSame('marzpay', $gateway->name());
    }

    /** @test */
    public function an_unknown_gateway_setting_falls_back_to_marzpay(): void
    {
        config(['services.payments.default' => 'does-not-exist']);

        $this->assertInstanceOf(MarzPayService::class, app(PaymentGateway::class));
    }

    /** @test */
    public function checkout_talks_to_whatever_gateway_is_bound(): void
    {
        $fake = new class implements PaymentGateway {
            public array $collected = [];
            public function name(): string { return 'fakepay'; }
            public function collect(array $data): array { $this->collected[] = $data; return ['status' => 'failed']; }
            public function status(string $reference): array { return []; }
        };
        $this->app->instance(PaymentGateway::class, $fake);

        $controller = app(\App\Http\Controllers\PaymentController::class);
        $prop = (new \ReflectionClass($controller))->getProperty('gateway');
        $prop->setAccessible(true);

        $this->assertSame($fake, $prop->getValue($controller));
    }
}
