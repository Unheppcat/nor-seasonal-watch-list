<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Show;
use App\Entity\View\RankedChoiceVoteTally;

final class RankedChoiceVoteTallyHelper
{
    private MinimaxRankHelper $helper;

    public function __construct(MinimaxRankHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param array $votesInfo
     * @param Show[] $shows
     * @return RankedChoiceVoteTally[]
     */
    public function getTallies(array $votesInfo, array $shows): array
    {
        $showsById = [];
        foreach ($shows as $show) {
            $showsById[$show->getId()] = $show->getEnglishTitle();
        }
        ksort($showsById);
        $this->helper->setTitles($showsById);

        $showAnilistIdsById = [];
        foreach ($shows as $show) {
            $showAnilistIdsById[$show->getId()] = $show->getAnilistId();
        }
        ksort($showAnilistIdsById);
        $this->helper->setAnilistIds($showAnilistIdsById);

        $rankingsByUser = [];
        foreach ($votesInfo as $voteInfo) {
            $userId = (int)$voteInfo['user_id'];
            $showId = (int)$voteInfo['show_id'];
            $rank = (string)$voteInfo['rank_choice'];   // value may be 'No opinion'
            if (!isset($rankingsByUser[$userId])) {
                $rankingsByUser[$userId] = [];
            }
            $rankingsByUser[$userId][$showId] = $rank;
        }

        foreach ($rankingsByUser as $rankingsBySingleUser) {
            ksort($rankingsBySingleUser);
            $this->helper->addBallot($rankingsBySingleUser);
        }

        return $this->helper->getRanks();
    }
}
