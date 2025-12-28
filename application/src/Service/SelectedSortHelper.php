<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

final class SelectedSortHelper
{
    /**
     * @param Request $request
     * @param string $listName
     * @return string|null
     */
    public function getSelectedSort(Request $request, string $listName): ?string
    {
        $sessionKey = 'sort_' . $listName;
        $selectedSortName = $request->query->get('sort');
        if ($selectedSortName === null) {
            $selectedSortName = $request->getSession()->get($sessionKey);
            if ($selectedSortName === null) {
                if ($listName === 'personal_watch') {
                    $selectedSortName = 'show_asc';
                } elseif ($listName === 'community_watch') {
                    $selectedSortName = 'show_asc';
                }
                if ($selectedSortName !== null) {
                    $request->getSession()->set($sessionKey, $selectedSortName);
                }
            }
        } else {
            $request->getSession()->set($sessionKey, $selectedSortName);
        }
        return $selectedSortName;
    }

}
