<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Election;
use App\Entity\View\RankedChoiceVoteTally;
use App\Entity\View\VoteTally;
use App\Repository\ElectionRepository;
use App\Repository\ElectionVoteRepository;
use App\Repository\ShowRepository;
use Doctrine\DBAL\Exception;
use RuntimeException;

final class VoterInfoHelper
{
    private ElectionRepository $electionRepository;
    private ShowRepository $showRepository;
    private ElectionVoteRepository $electionVoteRepository;
    private SimpleVoteTallyHelper $simpleVoteTallyHelper;
    private RankedChoiceVoteTallyHelper $rankedChoiceVoteTallyHelper;

    private ?array $voteTalliesForExport = null;
    private ?Election $election = null;
    private ExportHelper $exportHelper;

    /**
     * VoterInfoHelper constructor.
     * @param ElectionRepository $electionRepository
     * @param ShowRepository $showRepository
     * @param ElectionVoteRepository $electionVoteRepository
     * @param SimpleVoteTallyHelper $simpleVoteTallyHelper
     * @param RankedChoiceVoteTallyHelper $rankedChoiceVoteTallyHelper
     * @param ExportHelper $exportHelper
     */
    public function __construct(
        ElectionRepository $electionRepository,
        ShowRepository $showRepository,
        ElectionVoteRepository $electionVoteRepository,
        SimpleVoteTallyHelper $simpleVoteTallyHelper,
        RankedChoiceVoteTallyHelper $rankedChoiceVoteTallyHelper,
        ExportHelper $exportHelper
    ) {
        $this->electionRepository = $electionRepository;
        $this->showRepository = $showRepository;
        $this->electionVoteRepository = $electionVoteRepository;
        $this->simpleVoteTallyHelper = $simpleVoteTallyHelper;
        $this->rankedChoiceVoteTallyHelper = $rankedChoiceVoteTallyHelper;
        $this->exportHelper = $exportHelper;
    }

    /**
     * @param Election $election
     * @return array
     * @throws Exception
     */
    public function getInfo(
        Election $election
    ): array {
        $info = [];
        $info['electionIsActive'] = $this->electionRepository->electionIsActive();
        $info['shows'] = $this->showRepository->getShowsForSeasonElectionEligible($election->getSeason());
        $info['totalVoterCount'] = $this->electionVoteRepository->getVoterCountForElection($election);

        if ($election->getElectionType() === Election::RANKED_CHOICE_ELECTION) {
            $info['votesInfo'] = $this->electionVoteRepository->getRanksForElection($election);
            $info['voteTallies'] = $this->rankedChoiceVoteTallyHelper->getTallies($info['votesInfo'], $info['shows']);
        }
        if ($election->getElectionType() === Election::SIMPLE_ELECTION) {
            $info['votesInfo'] = $this->electionVoteRepository->getCountsForElection($election);
            $info['buffedTotalVoteCount'] = $this->electionVoteRepository->getBuffedVoteCountForElection($election);
            $info['voteTallies'] = $this->simpleVoteTallyHelper->getTallies($info['votesInfo'], $info['totalVoterCount'],
                $info['buffedTotalVoteCount'], $info['shows']);
        }

        return $info;
    }

    /**
     * @param Election $election
     * @throws Exception
     */
    public function initializeForExport(Election $election): void
    {
        $this->election = $election;
        $shows = $this->showRepository->getShowsForSeasonElectionEligible($election->getSeason());
        if ($election->getElectionType() === Election::SIMPLE_ELECTION) {
            $totalVoterCount = $this->electionVoteRepository->getVoterCountForElection($election);
            $votesInfo = $this->electionVoteRepository->getCountsForElection($election);
            $buffedTotalVoteCount = $this->electionVoteRepository->getBuffedVoteCountForElection($election);
            $this->voteTalliesForExport = $this->simpleVoteTallyHelper->getTallies($votesInfo,
                $totalVoterCount, $buffedTotalVoteCount, $shows);
        }
        if ($election->getElectionType() === Election::RANKED_CHOICE_ELECTION) {
            $votesInfo = $this->electionVoteRepository->getRanksForElection($election);
            $this->voteTalliesForExport = $this->rankedChoiceVoteTallyHelper->getTallies($votesInfo, $shows);
        }
    }

    public function writeExport($fp): void
    {
        if ($this->election === null || $this->voteTalliesForExport === null) {
            throw new RuntimeException('The VoterInfoHelper was not properly initialized.');
        }
        $this->writeExportHeader($fp);
        $this->writeExportBody($fp);
    }

    private function writeExportHeader($fp): void
    {
        if ($this->election->getElectionType() === Election::SIMPLE_ELECTION) {
            fwrite($fp, $this->exportHelper->arrayToCsv(['Show', 'Raw Votes', 'Buff', 'Calc Votes', '% of Voters', '% of Total']) . "\n");
        }
        if ($this->election->getElectionType() === Election::RANKED_CHOICE_ELECTION) {
            fwrite($fp, $this->exportHelper->arrayToCsv(['Show', 'Rank']) . "\n");
        }
    }

    private function writeExportBody($fp): void
    {
        foreach ($this->voteTalliesForExport as $voteTally) {
            $title = $voteTally->getShowCombinedTitle();
            if (!empty($voteTally->getRelatedShowNames())) {
                $title .= ' (and ' . count($voteTally->getRelatedShowNames()) . ' other seasons)';
            }
            if ($this->election->getElectionType() === Election::SIMPLE_ELECTION) {
                /** @var VoteTally $voteTally */
                fwrite($fp, $this->exportHelper->arrayToCsv([
                        $title,
                        $voteTally->getVoteCount(),
                        "'" . $voteTally->getBuffRule(),
                        $voteTally->getBuffedVoteCount(),
                        $voteTally->getVotePercentOfVoterTotal(),
                        $voteTally->getBuffedVotePercentOfTotal()
                    ]) . "\n");
            }
            if ($this->election->getElectionType() === Election::RANKED_CHOICE_ELECTION) {
                /** @var RankedChoiceVoteTally $voteTally */
                fwrite($fp, $this->exportHelper->arrayToCsv([
                        $title,
                        $voteTally->getRank(),
                    ]) . "\n");
            }
        }

    }


}
