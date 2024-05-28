<?php

namespace App\Tests\Mock;

use App\Service\BillingClient;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BillingClientMock extends BillingClient
{
    private string $userEmail = 'user@email.example';
    private string $adminEmail = 'user_admin@email.example';

    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        parent::__construct();
        $this->tokenStorage = $tokenStorage;
    }

    
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
            $response['refresh_token'] = "header.$token.verifySignature";

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
            $response['errors']['username'] = 'Пользователь с такой электронной почтой уже существует!';
            return $response;
        }

        $token = $this->getToken($arrayedCredentials['username']);

        $response['refresh_token'] = "header.$token.verifySignature";
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
                'username' => $payload['username'],
                'code' => 200
            ];
        } catch (\Exception $e) {
            return json_encode(['code' => 401, 'message' => 'Invalid JWT Token']);
        }
    }

    private function getToken($username, $roles = ['ROLE_USER'])
    {
        return base64_encode(json_encode([
                'username' => $username,
                'iat' => (new \DateTime('now'))->getTimestamp(),
                'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
                'roles' => $roles,
            ],
            JSON_THROW_ON_ERROR
        ));
    }

    public function refresh(string $refreshToken): array
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $token = base64_encode(json_encode([
            'username' => $user->getUserIdentifier(),
            'iat' => (new \DateTime('now'))->getTimestamp(),
            'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
            'roles' => $user->getRoles(),
        ], JSON_THROW_ON_ERROR));

        $response['token'] = "header." . $token . ".verifySignature";
        $response['refresh_token'] = 'refresh_token';
        return $response;
    }

    public function transactions(string $token, array $filter = null): array
    {
        return [
            [
                'id' => 1,
                "create_at" => "2024-05-28UTC08:16:12",
                "type" => "payment",
                "course_code" => "php",
                "amount" => 25999.9,
                'expires_at' => null,
            ],
            [
                'id' => 2,
                "create_at" => "2024-05-29UTC08:07:33",
                "type" => "payment",
                "course_code" => "js",
                "expires_at" => "2024-06-05UTC08:07:33",
                "amount" => 1999.9,
            ],
            [
                'id' => 3,
                "create_at" => "2024-05-27UTC08:02:56",
                "type" => "deposit",
                "amount" => 1000000,
            ],
        ];
    }
}
