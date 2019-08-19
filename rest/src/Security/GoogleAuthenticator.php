<?php

namespace App\Security;

use App\Entity\User;
use App\Model\EcorpUserManager;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class GoogleAuthenticator extends SocialAuthenticator
{
    /**
     * @var ClientRegistry
     */
    private $clientRegistry;

    /**
     * @var EcorpUserManager
     */
    private $userManager;

    /**
     * GoogleAuthenticator constructor.
     * @param ClientRegistry $clientRegistry
     * @param EcorpUserManager $userManager
     */
    public function __construct(ClientRegistry $clientRegistry, EcorpUserManager $userManager)
    {
        $this->clientRegistry = $clientRegistry;
        $this->userManager = $userManager;
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            '/connect/', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    public function supports(Request $request)
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function getCredentials(Request $request)
    {
        return $this->fetchAccessToken($this->getGoogleClient());
    }

    /**
     * @return GoogleClient
     */
    public function getGoogleClient()
    {
        return $this->clientRegistry->getClient('google');
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return User|null|object|UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var GoogleUser $googleUser */
        $googleUser = $this->getGoogleClient()
            ->fetchUserFromToken($credentials);

        // 1) have they logged in with Google before? Easy!
        $existingUser = $this->userManager->getUserRepository()
            ->findOneBy(['googleId' => $googleUser->getId()]);

        if ($existingUser) {
            //Cute! We have a user!
            return $userProvider->loadUserByUsername($existingUser->getUsername());
        }

        // 2) Never ever? Create (register) a one!
        //MOVE THIS TO METHOD
        $user = new User();
        $user
            ->setEmail($googleUser->getEmail())
            ->setFirstName($googleUser->getFirstName())
            ->setGoogleId($googleUser->getId())
            ->setAvatarUri($googleUser->getAvatar())
            ->setLastName($googleUser->getLastName())
            ->setPassword(null);

        $this->userManager->createUser($user);

        return $userProvider->loadUserByUsername($user->getUsername());
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return null|Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return null|Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }
}