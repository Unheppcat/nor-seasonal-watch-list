<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class SelectedAllWatchModeHelper
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * SelectedSeasonHelper constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return string
     */
    public function getSelectedMode(Request $request, User $user): string
    {
        $selectedMode = $request->query->get('mode');
        if ($selectedMode === null) {
            $selectedMode = $user->getPreferences()->getAllWatchesViewMode();
        }
        if ($selectedMode !== $user->getPreferences()->getAllWatchesViewMode()) {
            $prefs = $user->getPreferences();
            $prefs->setAllWatchesViewMode($selectedMode);
            $user->setPreferences($prefs);
            $this->em->persist($user);
            $this->em->flush();
        }
        return $selectedMode;
    }

}
