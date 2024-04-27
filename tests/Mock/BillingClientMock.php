<?php

namespace App\Tests\Mock;

use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{
    private string $userEmail = 'user@email.example';
    private string $adminEmail = 'user_admin@email.example';

    
    public function authenticate(string $credentials): array
    {
        $arrayedCredentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);

        if (($arrayedCredentials['username'] === $this->adminEmail
            || $arrayedCredentials['username'] === $this->userEmail)
            && $arrayedCredentials['password'] === $arrayedCredentials['username']
        ) {
            $roles = $arrayedCredentials['username'] === $this->adminEmail ? ['ROLE_SUPER_ADMIN'] : ['ROLE_USER'];

            $token = $token = $this->getToken($arrayedCredentials['username'], $roles);
            $response['token'] = "header.$token.verifySignature";

            return $response;
        }

        return ['code' => 401, "message" => "Invalid credentials."];
    }

    public function registraton(string $credentials): array
    {
        $arrayedCredentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);

        if ($arrayedCredentials['username'] === $this->adminEmail
            || $arrayedCredentials['username'] === $this->userEmail
        ) {
            $response['code'] = 401;
            $response['errors']['unique'] = 'Пользователь с такой электронной почтой уже существует!';
            return $response;
        }

        $token = $this->getToken($arrayedCredentials['username']);
        $response['token'] = "header.$token.verifySignature";
        return $response;
    }

    public function getCurrentUser(string $token): array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new \Exception();
            }

            $payload = json_decode(base64_decode($parts[1]), true, 512, JSON_THROW_ON_ERROR);

            return [
                'balance' => 1000.0,
                'roles' => $payload['roles'],
                'username' => $payload['email'],
                'code' => 200
            ];
        } catch (\Exception $e) {
            return json_encode(['code' => 401, 'message' => 'Invalid JWT Token']);
        }
    }

    private function getToken($username, $roles = ['ROLE_USER'])
    {
        return base64_encode(json_encode([
                'email' => $username,
                'iat' => (new \DateTime('now'))->getTimestamp(),
                'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
                'roles' => $roles,
            ],
            JSON_THROW_ON_ERROR
        ));
    }
}
