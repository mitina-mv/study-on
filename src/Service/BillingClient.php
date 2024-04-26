<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use App\Service\BillingRequstService;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class BillingClient
{
    /**
     * Адресс биллинга
     */
    private $billing;

    public function __construct()
    {
        $this->billing = $_ENV['BILLING_SERVER'];
    }


    public function authenticate(string $credentials) : array
    {
        $url = $this->billing . '/api/v1/auth';
        return BillingRequstService::post($url, $credentials);
    }

    public function getCurrentUser(string $token): array
    {
        $url = $this->billing . '/api/v1/users/current';
        return BillingRequstService::get($url, $token);
    }
}
