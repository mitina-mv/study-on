<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use JsonException;

class BillingRequstService
{
    public static function post(
        string $url,
        string $data = null,
        $token = null
    ) : array {
        $curl = curl_init($url);
        $options = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($token !== null) {
            $options[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $options);

        if ($data !== null) {
            curl_setopt(
                $curl,
                CURLOPT_POSTFIELDS,
                $data
            );
        }

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            throw new BillingUnavailableException('Сервис временно недоступен.');
        }
        
        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }
}
