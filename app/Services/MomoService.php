<?php
// app/Services/MomoService.php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Guid\Guid;
use Ramsey\Uuid\Guid\GuidInterface;

class MomoService
{
    protected $client;
    protected $config;
    protected $apiKey;

public function __construct()
{
    $this->config = [
        'base_uri' => config('services.momo.base_url'),
        'primary_key' => config('services.momo.primary_key'),
        'secondary_key' => config('services.momo.secondary_key'),
        'callback_url' => config('services.momo.callback_url'),
        'api_user_id' => config('services.momo.api_user_id'),
        'environment' => config('services.momo.env', 'sandbox')
    ];

    $this->client = new Client([
        'base_uri' => $this->config['base_uri'], // ✅ this is critical
    ]);

    $this->apiKey = $this->getApiKey();
}


    // Step 1: Create API User (one-time setup)
    public function createApiUser()
    {
        try {
            $response = $this->client->post('v1_0/apiuser', [
                'headers' => [
                    'X-Reference-Id' => $this->config['api_user_id'],
                    'Ocp-Apim-Subscription-Key' => $this->config['primary_key']
                ],
                'json' => [
                    'providerCallbackHost' => $this->config['callback_url']
                ]
            ]);

            return $response->getStatusCode() === 201;
        } catch (\Exception $e) {
            Log::error('Failed to create API user: ' . $e->getMessage());
            return false;
        }
    }

    // Step 2: Get API User details
    public function getApiUser()
    {
        try {
            $response = $this->client->get("v1_0/apiuser/{$this->config['api_user_id']}", [
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => $this->config['primary_key']
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to get API user: ' . $e->getMessage());
            return null;
        }
    }

    // Step 3: Create API Key (one-time)
    protected function getApiKey()
    {
        return Cache::remember('momo_api_key', now()->addHours(24), function () {
            try {
                $response = $this->client->post("v1_0/apiuser/{$this->config['api_user_id']}/apikey", [
                    'headers' => [
                        'Ocp-Apim-Subscription-Key' => $this->config['primary_key']
                    ]
                ]);

                $data = json_decode($response->getBody(), true);
                return $data['apiKey'] ?? null;
            } catch (\Exception $e) {
                Log::error('Failed to get API key: ' . $e->getMessage());
                return null;
            }
        });
    }

    // Step 4: Get Access Token
    protected function getAccessToken()
    {
        return Cache::remember('momo_access_token', now()->addMinutes(30), function () {
            try {
                $response = $this->client->post('collection/token/', [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($this->config['api_user_id'] . ':' . $this->apiKey),
                        'Ocp-Apim-Subscription-Key' => $this->config['primary_key']
                    ]
                ]);

                $data = json_decode($response->getBody(), true);
                return $data['access_token'] ?? null;
            } catch (\Exception $e) {
                Log::error('Failed to get access token: ' . $e->getMessage());
                return null;
            }
        });
    }

    // Step 5: Request Payment
    public function requestToPay($amount, $phoneNumber, $externalId, $payerMessage = '', $payeeNote = '')
{
    // Generate a UUID for X-Reference-Id
    $referenceId = Guid::uuid4()->toString(); // Generates a random UUID (v4)
    
    $accessToken = $this->getAccessToken();

    // Ensure the amount is formatted as a string, as required by MoMo API
    $formattedAmount = number_format($amount, 2, '.', ''); 

    try {
        $response = $this->client->post('collection/v1_0/requesttopay', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Reference-Id' => $referenceId,
                'X-Target-Environment' => $this->config['environment'],
                'Ocp-Apim-Subscription-Key' => $this->config['primary_key'],
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'amount' => $formattedAmount, // Ensure it's a string
                'currency' => 'EUR',
                'externalId' => $externalId, // Use the unique externalId
                'payer' => [
                    'partyIdType' => 'MSISDN', // Payer's partyIdType
                    'partyId' => $phoneNumber // Ensure it's the payer's phone number
                ],
                'payerMessage' => $payerMessage, // Custom payer message
                'payeeNote' => $payeeNote // Custom note for payee
            ]
        ]);

        return [
            'success' => $response->getStatusCode() === 202,
            'reference_id' => $referenceId
        ];
    } catch (\Exception $e) {
        Log::error('Payment request failed: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

    // Step 6: Check Payment Status
    public function getPaymentStatus($referenceId)
    {
        $accessToken = $this->getAccessToken();

        try {
            $response = $this->client->get("collection/v1_0/requesttopay/{$referenceId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Ocp-Apim-Subscription-Key' => $this->config['primary_key'],
                    'X-Target-Environment' => $this->config['environment']
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to check payment status: ' . $e->getMessage());
            return null;
        }
    }

    // Step 7: Get Account Balance
    public function getAccountBalance()
    {
        $accessToken = $this->getAccessToken();

        try {
            $response = $this->client->get('collection/v1_0/account/balance', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Ocp-Apim-Subscription-Key' => $this->config['primary_key'],
                    'X-Target-Environment' => $this->config['environment']
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to get account balance: ' . $e->getMessage());
            return null;
        }
    }
}