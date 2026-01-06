<?php

namespace App\EventSubscriber;

use App\Controller\DefaultController;
use App\Controller\MyVoteController;
use App\Entity\User;
use App\Service\RoleRefreshService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class VotingRoleRefreshSubscriber implements EventSubscriberInterface
{
    private RoleRefreshService $roleRefreshService;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        RoleRefreshService $roleRefreshService,
        TokenStorageInterface $tokenStorage
    ) {
        $this->roleRefreshService = $roleRefreshService;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Only process main requests (not sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        $controller = $event->getController();

        // Controller can be a class or a Closure
        if (!is_array($controller)) {
            return;
        }

        // Get the current user
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!($user instanceof User)) {
            return;
        }

        // Refresh roles if they're older than 1 hour (3600 seconds)
        // This happens automatically in the background before the controller runs
        $this->roleRefreshService->refreshUserRoles($user, 3600);
    }
}
