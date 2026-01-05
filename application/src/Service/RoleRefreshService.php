<?php

namespace App\Service;

use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RoleRefreshService
{
    private EntityManagerInterface $em;
    private DiscordApi $discordApi;
    private string $norGuildId;
    private TokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        DiscordApi $discordApi,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        string $norGuildId
    ) {
        $this->em = $em;
        $this->discordApi = $discordApi;
        $this->norGuildId = $norGuildId;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * Refresh a user's roles from Discord
     *
     * @param User $user
     * @param int $maxAgeInSeconds Only refresh if last refresh was older than this (default: 1 hour)
     * @return bool True if roles were refreshed, false if skipped or failed
     */
    public function refreshUserRoles(User $user, int $maxAgeInSeconds = 3600): bool
    {
        // Check if refresh is needed
        $lastRefresh = $user->getRolesLastRefreshed();
        if ($lastRefresh !== null) {
            $now = new DateTime();
            $age = $now->getTimestamp() - $lastRefresh->getTimestamp();
            if ($age < $maxAgeInSeconds) {
                // Roles are fresh enough, skip refresh
                return false;
            }
        }

        // Attempt to refresh roles from Discord
        try {
            $discordToken = $user->getDiscordToken();
            if (!$discordToken) {
                $this->logger->warning('Cannot refresh roles: user has no Discord token', ['user_id' => $user->getId()]);
                return false;
            }

            $this->discordApi->initialize($discordToken);
            $existingRoles = $user->getRoles();
            $newRoles = $this->updateDiscordRoles($user->getDiscordId(), $existingRoles);

            // Only update if roles actually changed
            if (!$this->arraysHaveSameValues($existingRoles, $newRoles)) {
                $user->setRoles($newRoles);
                $this->logger->info('Refreshed user roles from Discord', [
                    'user_id' => $user->getId(),
                    'old_roles' => $existingRoles,
                    'new_roles' => $newRoles
                ]);

                // Update the security token if this user is currently authenticated
                $this->updateSecurityToken($user, $newRoles);
            }

            // Update the refresh timestamp
            $user->setRolesLastRefreshed(new DateTime());
            $this->em->persist($user);
            $this->em->flush();

            return true;
        } catch (GuzzleException | Exception $e) {
            $this->logger->error('Failed to refresh user roles from Discord', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update the security token with new roles (so the change takes effect immediately)
     *
     * @param User $user
     * @param array $newRoles
     */
    private function updateSecurityToken(User $user, array $newRoles): void
    {
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof User && $token->getUser()->getId() === $user->getId()) {
            // Create a new token with updated roles
            $newToken = new UsernamePasswordToken($user, 'main', $newRoles);
            $this->tokenStorage->setToken($newToken);
        }
    }

    /**
     * Fetch Discord roles and map them to application roles
     * (Extracted from AppDiscordAuthenticator)
     *
     * @throws GuzzleException
     * @throws \JsonException
     */
    private function updateDiscordRoles(string $userDiscordId, array $existingRoles): array
    {
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
            || isset($userDiscordRoles['1455327621729746944'])  // Unheppcat server SWL_STAFF
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

        if (
            isset($userDiscordRoles['1456162429657682115'])   // Unheppcat Special Election Voter
            || isset($userDiscordRoles['1457549326233375005']) // NOR Special Election Access
        ) {
            $rolesToAdd[] = 'ROLE_SWL_SPECIAL_ELECTION_VOTER';
        } else {
            $rolesToRemove[] = 'ROLE_SWL_SPECIAL_ELECTION_VOTER';
        }

        $newRoles = array_unique(array_merge($existingRoles, $rolesToAdd));
        return array_values(array_diff($newRoles, $rolesToRemove));
    }

    private function arraysHaveSameValues(array $a, array $b): bool
    {
        return (count($a) === count($b) && !array_diff($a, $b));
    }
}
