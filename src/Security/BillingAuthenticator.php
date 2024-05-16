<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Exception;
use JsonException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private BillingClient $billingClient,
    ) {
    }

    public function authenticate(Request $request) : SelfValidatingPassport
    {
        $email = $request->request->get('email', null);

        $credentials = json_encode([
            'username' => $email,
            'password' => $request->request->get('password', null),
        ]);

        // запрос на авторизацию
        $loaderUser = function () use ($credentials, $request): UserInterface {
            try {
                $response = $this->billingClient->authenticate($credentials);
                if (isset($response['code'])) {
                    throw new BillingUnavailableException($response['message']);
                }
                // запрос на получение текущего пользователя
                $userResponse = $this->billingClient->getCurrentUser($response['token']);
            } catch (BillingUnavailableException | JsonException $e) {
                throw new CustomUserMessageAuthenticationException(
                    'Произошла ошибка во время авторизации: ' . $e->getMessage()
                );
            }

            $user = new User();
            $user->setApiToken($response['token']);
            $user->setRefreshToken($response['refresh_token']);
            $user->setRoles($userResponse['roles']);
            $user->setBalance($userResponse['balance']);
            $user->setEmail($userResponse['username']);

            return $user;
        };

        return new SelfValidatingPassport(
            new UserBadge($email, $loaderUser),
            [
                new CsrfTokenBadge('authenticate', $request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
