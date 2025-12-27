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
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class AppDiscordAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $em;
    private RouterInterface $router;
    private string $norGuildId;
    private DiscordApi $discordApi;
    private RequestStack $requestStack;

    /**
     * AppDiscordAuthenticator constructor.
     */
    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        RouterInterface $router,
        DiscordApi $discordApi,
        RequestStack $requestStack,
        string $norGuildId
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
        $this->norGuildId = $norGuildId;
        $this->discordApi = $discordApi;
        $this->requestStack = $requestStack;
    }

    public function supports(Request $request): ?bool
    {
        $requestedRoute = $request->attributes->get('_route');
        return ($requestedRoute === 'connect_discord_check' || $requestedRoute === 'secure_connect_discord_check');
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->getDiscordClient();
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                return $this->getUserFromToken($accessToken, $client);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $session = $this->requestStack->getSession();
        $targetUrl = $session->get('loginOriginalRequestUri');
        if ($targetUrl === null) {
            $targetUrl = $this->router->generate('https_default');
        }
        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $session = $this->requestStack->getSession();
        $session->getFlashBag()->add('danger', strtr($exception->getMessageKey(), $exception->getMessageData()));
        $targetUrl = $this->router->generate('https_default');
        return new RedirectResponse($targetUrl);
    }

    private function getDiscordClient(): OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient('discord');
    }

    private function getUserFromToken(AccessToken $credentials, OAuth2ClientInterface $client): User
    {
        /** @var DiscordResourceOwner $discordUser */
        $discordUser = $client->fetchUserFromToken($credentials);
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

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    private function getDiscordNickname(string $userToken, string $userDiscordId): ?string
    {
        $this->discordApi->initialize($userToken);
        return $this->discordApi->getNicknameForMember($this->norGuildId, $userDiscordId);
    }

    /**
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
