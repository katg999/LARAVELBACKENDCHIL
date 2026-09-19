<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarzPayService implements PaymentGateway
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $authHeader;

    public function __construct()
    {
        $this->baseUrl = config('services.marzpay.base_url');
        $this->apiKey = config('services.marzpay.api_key');
        $this->apiSecret = config('services.marzpay.api_secret');
        $this->authHeader = 'Basic ' . config('services.marzpay.auth_header');
    }

    public function name(): string
    {
        return 'marzpay';
    }

    public function collect(array $data): array
    {
        return $this->collectMoney($data);
    }

    public function status(string $reference): array
    {
        return $this->getTransaction($reference);
    }

    /**
     * Get account balance
     */
    public function getBalance()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/balance");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Balance Check Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get available services
     */
    public function getServices()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/services");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Services Check Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get transactions with optional filters
     */
    public function getTransactions(array $filters = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/transactions", $filters);

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Transactions Check Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get specific transaction details
     */
    public function getTransaction($uuid)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/transactions/{$uuid}");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Transaction Check Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Collect money from customer
     */
    public function collectMoney(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/collect-money", $data);

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Collection Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get collection details
     */
    public function getCollection($uuid)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/collect-money/{$uuid}");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Collection Details Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get collection services
     */
    public function getCollectionServices()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/collect-money/services");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Collection Services Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send money to customer
     */
    public function sendMoney(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/send-money", $data);

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Send Money Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get send money details
     */
    public function getSendMoney($uuid)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/send-money/{$uuid}");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Send Money Details Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get send money services
     */
    public function getSendMoneyServices()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/send-money/services");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Send Money Services Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create payment link
     */
    public function createPaymentLink(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/payment-links", $data);

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Payment Link Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get payment link details
     */
    public function getPaymentLink($uuid)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/payment-links/{$uuid}");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Payment Link Details Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * List payment links
     */
    public function getPaymentLinks(array $filters = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/payment-links", $filters);

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Payment Links List Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update payment link
     */
    public function updatePaymentLink($uuid, array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->put("{$this->baseUrl}/payment-links/{$uuid}", $data);

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Payment Link Update Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete payment link
     */
    public function deletePaymentLink($uuid)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->delete("{$this->baseUrl}/payment-links/{$uuid}");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Payment Link Delete Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify phone number
     */
    public function verifyPhone($phoneNumber)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/phone-verification/verify", [
                'phone_number' => $phoneNumber
            ]);

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Phone Verification Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get phone verification service info
     */
    public function getPhoneVerificationServiceInfo()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/phone-verification/service-info");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Phone Verification Service Info Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get phone verification subscription status
     */
    public function getPhoneVerificationSubscriptionStatus()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/phone-verification/subscription-status");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Phone Verification Subscription Status Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get account details
     */
    public function getAccountDetails()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/account");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Account Details Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update account details
     */
    public function updateAccountDetails(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->put("{$this->baseUrl}/account", $data);

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Account Update Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create webhook
     */
    public function createWebhook(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/webhooks", $data);

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Webhook Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * List webhooks
     */
    public function getWebhooks()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/webhooks");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Webhooks List Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get webhook details
     */
    public function getWebhook($uuid)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/webhooks/{$uuid}");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Webhook Details Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update webhook
     */
    public function updateWebhook($uuid, array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->put("{$this->baseUrl}/webhooks/{$uuid}", $data);

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Webhook Update Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook($uuid)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader,
                'Content-Type' => 'application/json',
            ])->delete("{$this->baseUrl}/webhooks/{$uuid}");

            if ($response->failed()) {
                throw new \Exception('MarzPay API Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MarzPay Webhook Delete Error: ' . $e->getMessage());
            throw $e;
        }
    }
}