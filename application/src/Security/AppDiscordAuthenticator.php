<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/** @noinspection PhpUndefinedClassInspection */

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\DiscordApi;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class AppDiscordAuthenticator extends SocialAuthenticator // AbstractGuardAuthenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $em;
    private RouterInterface $router;
    /**
     * @var string
     */
    private string $norGuildId;
    /**
     * @var DiscordApi
     */
    private DiscordApi $discordApi;
    /**
     * @var FlashBagInterface
     */
    private FlashBagInterface $flashBag;

    /**
     * AppDiscordAuthenticator constructor.
     * @param ClientRegistry $clientRegistry
     * @param EntityManagerInterface $em
     * @param RouterInterface $router
     * @param DiscordApi $discordApi
     * @param FlashBagInterface $flashBag
     * @param string $norGuildId
     */
    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        RouterInterface $router,
        DiscordApi $discordApi,
        FlashBagInterface $flashBag,
        string $norGuildId
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
        $this->norGuildId = $norGuildId;
        $this->discordApi = $discordApi;
        $this->flashBag = $flashBag;
    }

    public function supports(Request $request): bool
    {
        $requestedRoute = $request->attributes->get('_route');
        return ($requestedRoute === 'connect_discord_check' || $requestedRoute === 'secure_connect_discord_check');
    }

    public function getCredentials(Request $request): AccessToken
    {
        return $this->fetchAccessToken($this->getDiscordClient());
    }

    public function getUser($credentials, UserProviderInterface $userProvider): User
    {
        /** @var DiscordResourceOwner $discordUser */
        $discordUser = $this->getDiscordClient()->fetchUserFromToken($credentials);
        $discordId = $discordUser->getId();
        $localUsername = $discordUser->getUsername() . '#' . $discordUser->getDiscriminator();

        /** @var UserRepository $userRepo */
        $userRepo = $this->em->getRepository(User::class);
        /** @var User|null $existingUser */
        $existingUser = null;
        if ($discordId) {
            $existingUsers = $userRepo->findAllByDiscordId($discordId);
            if ($existingUsers) {
                // Take the last, which we hope is the most current and active
                $existingUser = array_pop($existingUsers);
            }
        }
        if (!$existingUser) {
            $existingUser = $this->em->getRepository(User::class)
                ->findOneBy(['username' => $localUsername]);
        }
        if ($existingUser) {
            $existingRoles = $existingUser->getRoles();
            try {
                $newRoles = $this->updateDiscordRoles($credentials->getToken(), $existingRoles, $discordId);
                if (!$this->arraysHaveSameValues($existingRoles, $newRoles)) {
                    $existingUser->setRoles($newRoles);
                }
                $nickname = $this->getDiscordNickname($credentials->getToken(), $discordId);
                $existingUser->setDisplayName($nickname);
                if ($existingUser->getDiscordId() !== $discordId) {
                    $existingUser->setDiscordId($discordId);
                }
                if ($existingUser->getUsername() !== $localUsername) {
                    try {
                        // Remove potential collision
                        $priorUser = $userRepo->findByUsername($localUsername);
                        if ($priorUser) {
                            $priorUser->setUsername(sprintf("%s-%s", $localUsername, $priorUser->getId()));
                            $this->em->persist($priorUser);
                        }
                        $existingUser->setUsername($localUsername);
                    } catch (Exception $e) {
                        // Leave username unchanged for now
                    }
                }
                $this->em->persist($existingUser);
                $this->em->flush();
            } catch (GuzzleException|Exception $e) {
                // Leave existing roles for now
            }

            return $existingUser;
        }

        $user = new User();
        $user->setDiscordId($discordId);
        $user->setDiscordAvatar($discordUser->getAvatarHash());
        $user->setDiscordUsername($discordUser->getUsername());
        $user->setDiscordDiscriminator($discordUser->getDiscriminator());
        $user->setUsername($localUsername);
        $user->setDiscordToken($credentials->getToken());
        $user->setDiscordRefreshToken($credentials->getRefreshToken());
        $user->setDiscordTokenExpires($credentials->getExpires());
        try {
            $user->setRoles($this->updateDiscordRoles($credentials->getToken(), ['ROLE_USER'], $discordId));
        } catch (GuzzleException|Exception $e) {
            $user->setRoles(['ROLE_USER']);
        }
        try {
            $nickname = $this->getDiscordNickname($credentials->getToken(), $discordId);
            $user->setDisplayName($nickname);
        } catch (GuzzleException|Exception $e) {
            $user->setDisplayName(null);
        }

        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return ($credentials !== null);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->flashBag->add('danger', strtr($exception->getMessageKey(), $exception->getMessageData()));
        $targetUrl = $this->router->generate('https_default');
        return new RedirectResponse($targetUrl);
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return RedirectResponse|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?RedirectResponse
    {
        // Go home
        $targetUrl = $request->getSession()->get('loginOriginalRequestUri');
        if ($targetUrl === null) {
            $targetUrl = $this->router->generate('https_default');
        }
        return new RedirectResponse($targetUrl);
        // Or allow the original controller to process this request
        // return null;
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        $request->getSession()->set('loginOriginalRequestUri', $request->getRequestUri());
        return new RedirectResponse(
            '/discord/connect',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * @return OAuth2ClientInterface
     */
    private function getDiscordClient(): OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient('discord');
    }

    /**
     * @param string $userToken
     * @param string $userDiscordId
     * @return string|null
     * @throws GuzzleException
     * @throws JsonException
     */
    private function getDiscordNickname(string $userToken, string $userDiscordId): ?string
    {
        $this->discordApi->initialize($userToken);
        return $this->discordApi->getNicknameForMember($this->norGuildId, $userDiscordId);
    }

    /**
     * @param string $userToken
     * @param array $existingRoles
     * @param string $userDiscordId
     * @return array
     * @throws GuzzleException|JsonException
     */
    private function updateDiscordRoles(string $userToken, array $existingRoles, string $userDiscordId): array
    {
        $this->discordApi->initialize($userToken);
        $rolesToAdd = [];
        $rolesToRemove = [];
        $userDiscordRoles = $this->discordApi->getGuildRolesForMember($this->norGuildId, $userDiscordId);
        if (
            isset($userDiscordRoles['807643180349915176'])      // Unheppcat server SWL_ADMIN
            || isset($userDiscordRoles['667818223374172183'])   // NOR Moderator
            || isset($userDiscordRoles['596493343354126346'])   // NOR Admin
            || isset($userDiscordRoles['596493451810570254'])   // NOR Th8a
            || isset($userDiscordRoles['596493645046218754'])   // NOR Community Manager
        ) {
            $rolesToAdd[] = 'ROLE_SWL_ADMIN';
        } else {
            $rolesToRemove[] = 'ROLE_SWL_ADMIN';
        }

        if (
            isset($userDiscordRoles['881961735488172093'])      // NOR Staff
        ) {
            $rolesToAdd[] = 'ROLE_SWL_STAFF';
        } else {
            $rolesToRemove[] = 'ROLE_SWL_STAFF';
        }

        if (
            isset($userDiscordRoles['807642761338945546'])      // Unheppcat server SWL_USER
            || isset($userDiscordRoles['596496447386419213'])   // NOR New User
            || isset($userDiscordRoles['596493814152036352'])   // NOR Regular
        ) {
            $rolesToAdd[] = 'ROLE_SWL_USER';
        } else {
            $rolesToRemove[] = 'ROLE_SWL_USER';
        }
        $newRoles = array_unique(array_merge($existingRoles, $rolesToAdd));
        return array_diff($newRoles, $rolesToRemove);
    }

    private function arraysHaveSameValues(array $a, array $b): bool
    {
        return (count($a) === count($b) && !array_diff($a, $b));
    }

}
