<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DiscordController extends AbstractController
{
    /**
     * Link to this controller to start the "connect" process
     *
     * @param ClientRegistry $clientRegistry
     * @param string $discordRedirectUri
     * @return RedirectResponse
     */
    #[Route('/discord/connect', name: 'connect_discord_start')]
    public function connectAction(ClientRegistry $clientRegistry, string $discordRedirectUri): RedirectResponse
    {
        return $clientRegistry
            ->getClient('discord')
            ->redirect(
                ['identify'],
                ['redirect_uri' => $discordRedirectUri]
            );
    }

    /**
     * After going to Discord, the user will be redirected back here because this is the
     * 'redirect_route' configured in config/packages/knpu_oauth2_client.yaml
     *
     * @param Request $request
     * @param ClientRegistry $clientRegistry
     *
     * @return void
     */
    #[Route('/discord_check', name: 'secure_connect_discord_check', schemes: ['https'])]
    public function secureConnectCheckAction(
        Request $request,
        ClientRegistry $clientRegistry
    ): void {
        $this->connectCheckAction($request, $clientRegistry);
    }

    /**
     * After going to Discord, the user will be redirected back here because this is the
     * 'redirect_route' configured in config/packages/knpu_oauth2_client.yaml
     *
     * @param Request $request
     * @param ClientRegistry $clientRegistry
     *
     */
    #[Route('/discord_check', name: 'connect_discord_check', schemes: ['http'])]
    public function connectCheckAction(
        Request $request,
        ClientRegistry $clientRegistry
    ): void {
        // This method left blank, authentication handled in the Guard authenticator
    }

    /**
     * Logout from the discord session
     *
     */
    #[Route('/discord/disconnect', name: 'connect_discord_disconnect', methods: ['GET'])]
    public function disconnectAction(): void
    {
        // This method left blank, handled in the Guard authenticator
    }

}
