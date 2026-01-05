<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DiscordApi;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DiagnosticController extends AbstractController
{
    /**
     * @throws GuzzleException
     */
    #[Route('/diagnostic/roles', name: 'diagnostic_roles')]
    public function checkRoles(
        EntityManagerInterface $em,
        DiscordApi $discordApi,
        string $norGuildId
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        // Get roles from current security token (session)
        $tokenRoles = $user->getRoles();

        // Refresh user from database to get persisted roles
        $em->refresh($user);
        $dbRoles = $user->getRoles();

        // Try to get Discord roles
        /** @noinspection PhpUnusedLocalVariableInspection */
        $discordRoles = null;
        $discordRoleIds = [];
        try {
            $discordApi->initialize($user->getDiscordToken());
            $discordRoleIds = $discordApi->getGuildRolesForMember($norGuildId, $user->getDiscordId());

            // Check which special election roles they have
            $discordRoles = [
                'has_unheppcat_special_election_voter' => isset($discordRoleIds['1456162429657682115']),
                'has_nor_special_election_access' => isset($discordRoleIds['1457549326233375005']),
            ];
        } catch (Exception $e) {
            $discordRoles = ['error' => $e->getMessage()];
        }

        $response = new JsonResponse([
            'user_id' => $user->getId(),
            'discord_id' => $user->getDiscordId(),
            'username' => $user->getUsername(),
            'roles_from_security_token' => $tokenRoles,
            'roles_from_database' => $dbRoles,
            'discord_roles' => $discordRoles,
            'discord_role_ids' => array_keys($discordRoleIds),
            'has_special_election_voter_in_token' => in_array('ROLE_SWL_SPECIAL_ELECTION_VOTER', $tokenRoles, true),
            'has_special_election_voter_in_db' => in_array('ROLE_SWL_SPECIAL_ELECTION_VOTER', $dbRoles, true),
            'isGranted_check' => $this->isGranted('ROLE_SWL_SPECIAL_ELECTION_VOTER'),
            'note' => 'If token roles differ from DB roles, you need to logout and login again to refresh your session',
        ]);

        $response->setEncodingOptions(JSON_PRETTY_PRINT);

        return $response;
    }
}
