<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Show;
use App\Entity\View\VoteTally;

final class SimpleVoteTallyHelper
{
    /**
     * @param array $votesInfo
     * @param int $totalVoterCount
     * @param int $buffedTotalVoteCount
     * @param Show[] $shows
     * @return VoteTally[]
     */
    public function getTallies(array $votesInfo, int $totalVoterCount, int $buffedTotalVoteCount, array $shows): array
    {
        $voteTallies = [];
        $totalVotes = 0;
        $buffedTotalVotes = 0;
        foreach ($votesInfo as $voteInfo) {
            $totalVotes += $voteInfo['vote_count'];
            $buffedTotalVotes += $voteInfo['buffed_vote_count'];
        }
        foreach ($votesInfo as $key => $voteInfo) {
            $voteTally = new VoteTally();
            $voteTally->setId($key);
            $voteTally->setShowId((int)$voteInfo['show_id']);
            $voteTally->setShowJapaneseTitle((string)$voteInfo['japanese_title']);
            $voteTally->setShowFullJapaneseTitle((string)$voteInfo['full_japanese_title']);
            $voteTally->setShowEnglishTitle((string)$voteInfo['english_title']);
            $voteTally->setAnilistId((string)$voteInfo['anilist_id']);
            $voteTally->setVoteCount((int)$voteInfo['vote_count']);
            $voteTally->setBuffedVoteCount((int)$voteInfo['buffed_vote_count']);
            $voteTally->setBuffRule($voteInfo['buff_rule'] ?? '');
            $voteTally->setVotePercentOfTotal($this->calculatePercent((int)$voteInfo['vote_count'], $totalVotes));
            $voteTally->setBuffedVotePercentOfTotal($this->calculatePercent((int)$voteInfo['buffed_vote_count'], $buffedTotalVotes));
            $voteTally->setVotePercentOfVoterTotal($this->calculatePercent((int)$voteInfo['vote_count'], $totalVoterCount));
            $voteTally->setBuffedVotePercentOfVoterTotal($this->calculatePercent((int)$voteInfo['buffed_vote_count'], $buffedTotalVoteCount));
            $voteTallies[] = $voteTally;
            foreach ($shows as $showsKey => $show) {
                if ($show->getId() === $voteTally->getShowId()) {
                    $voteTally->setShowCombinedTitle($show->getVoteStyleTitles());
                    if ($show->getRelatedShows()) {
                        $relatedShowNames = [];
                        foreach ($show->getRelatedShows() as $relatedShow) {
                            $relatedShowNames[] = $relatedShow->getVoteStyleTitles();
                        }
                        $voteTally->setRelatedShowNames($relatedShowNames);
                    }
                    unset($shows[$showsKey]);
                    break;
                }
            }
        }

        // Remaining $shows got zero votes
        $nextVoteTallyId = count($voteTallies);
        foreach ($shows as $show) {
            $nextVoteTallyId++;
            $voteTally = new VoteTally();
            $voteTally->setId($nextVoteTallyId);
            $voteTally->setShowId($show->getId());
            $voteTally->setShowCombinedTitle((string)$show->getVoteStyleTitles());
            $voteTally->setShowJapaneseTitle((string)$show->getJapaneseTitle());
            $voteTally->setShowFullJapaneseTitle((string)$show->getFullJapaneseTitle());
            $voteTally->setShowEnglishTitle((string)$show->getEnglishTitle());
            $voteTally->setVoteCount(0);
            $voteTally->setBuffedVoteCount(0);
            $voteTally->setVotePercentOfTotal(0.0);
            $voteTally->setBuffedVotePercentOfTotal(0.0);
            $voteTally->setRelatedShowNames([]);
            if ($show->getRelatedShows()) {
                foreach ($show->getRelatedShows() as $relatedShow) {
                    $voteTally->addRelatedShowName($relatedShow->getVoteStyleTitles());
                }
            }
            $voteTallies[] = $voteTally;
        }
        return $voteTallies;
    }

    private function calculatePercent(int $count, int $totalCount): float
    {
        if ($totalCount > 0) {
            return round(($count / $totalCount) * 100, 1);
        }
        return 0;
    }


}
