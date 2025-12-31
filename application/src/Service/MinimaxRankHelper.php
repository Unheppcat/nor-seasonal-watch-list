<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Service;

use App\Entity\View\RankingResult;
use Exception;

final class MinimaxRankHelper
{
    private array $candidates = [];

    private array $titles = [];

    private array $anilistIds = [];

    private array $ballots = [];

    private ?int $numberOfWinners = null;
    private bool $noOpinionIsWorstRank;

    /**
     * @param bool           $noOpinionIsWorstRank
     */
    public function __construct(
        bool $noOpinionIsWorstRank
    ) {
        $this->noOpinionIsWorstRank = $noOpinionIsWorstRank;
    }

    public function setNumberOfWinners(?int $n): void
    {
        $this->numberOfWinners = $n;
    }

    public function setTitles(array $titles): void
    {
        // Reset keys to be 0-based
        $this->titles = array_values($titles);
        $this->candidates = [];
        foreach ($this->titles as $key => $value) {
            $this->candidates[] = $key;
        }
    }

    /**
     * @param array $anilistIds
     * @return void
     */
    public function setAnilistIds(array $anilistIds): void
    {
        $this->anilistIds = array_values($anilistIds);
    }

    public function addBallot(array $ballot): void
    {
        // Reset keys to be 0-based
        $thisBallot = array_values($ballot);
        // Convert 'No opinion' into null, other values to int
        foreach($thisBallot as $key => $value) {
            if ( empty($value) || strtolower($value) === 'no opinion') {
                $thisBallot[$key] = null;
            } else {
                $thisBallot[$key] = (int)$value;
            }
        }
        $this->ballots[] = $thisBallot;
    }

    private function getNumberOfWinners(): int
    {
        return $this->numberOfWinners ?? count($this->candidates);
    }

    private function getNumberOfTitles(): int
    {
        return count($this->titles);
    }

    private function getNumberOfCandidates(): int
    {
        return count($this->candidates);
    }

    /**
     * @param array $candidates
     * @param array $defeats
     * @return array
     */
    private function maxima(array $candidates, array $defeats): array
    {
        $isFirst = true;
        $maximaList = [];
        $maximumValue = [];
        foreach ($candidates as $key => $candidate) {
            if ($isFirst) {
                $isFirst = false;
                $maximaList = [$candidate];
                $maximumValue = $defeats[$key];
                continue;
            }

            $currentValue = $defeats[$key];

            $cmp = $this->compareDefeats($currentValue, $maximumValue);
            if ($cmp === 1) {
                $maximaList = [$candidate];
                $maximumValue = $currentValue;
            } elseif ($cmp === 0) {
                $maximaList[] = $candidate;
            }
        }

        return $maximaList;
    }

    private function compareDefeats(array $defeat1, array $defeat2): int
    {
        $defeatSize = count($defeat1);
        for ($i = 0; $i < $defeatSize; $i++) {
            if (
                $defeat1[$i][0] > $defeat2[$i][0]
                || ($defeat1[$i][0] === $defeat2[$i][0] && $defeat1[$i][1] > $defeat2[$i][1])
            ) {
                return 1;
            }
            if (
                $defeat1[$i][0] < $defeat2[$i][0]
                || ($defeat1[$i][0] === $defeat2[$i][0] && $defeat1[$i][1] < $defeat2[$i][1])
            ) {
                return -1;
            }
        }
        return 0;
    }

    /**
     * @return RankingResult[]
     */
    public function getRanks(): array
    {
        try {
            $results = [];
            $topOfRange = $this->getNumberOfWinners();
            $nT = $this->getNumberOfTitles();
            $nB = count($this->ballots);

            // Handle the before-election-starts case of no ballots entered
            if ($nB === 0) {
                for ($i = 0; $i < $nT; $i++) {
                    $results[] = new RankingResult($this->titles[$i], $this->anilistIds[$i], 0);
                }
                return $results;
            }

            for ($winnerRank = 1; $winnerRank <= $topOfRange; $winnerRank++) {

                // $preferenceMatrix[$a][$b] stores how many people ranked $a above $b
                // Create NxN nested array filled with 0s
                $preferenceMatrix = array_fill(0, $nT, array_fill(0, $nT, 0));

                $nC = $this->getNumberOfCandidates();

                // Compare all candidates pairwise
                foreach ($this->ballots as $ballot) {
                    for ($i = 0; $i < $nC; $i++) {
                        $iKey = $this->candidates[$i];
                        for ($j = 0; $j < $nC; $j++) {
                            $jKey = $this->candidates[$j];
                            if ($iKey === $jKey) {
                                continue;
                            }
                            $ballotI = $ballot[$iKey];
                            $ballotJ = $ballot[$jKey];

                            if ($this->noOpinionIsWorstRank) {
                                // 'Madi' version, which counts no opinion as worse than any explicit ranking.
                                // Two paired no opinions still counted as a tie.

                                // 'No opinion' for both candidates counts as a tie.
                                if ($ballotI === null && $ballotJ === null) {
                                    continue;
                                }
                                if ($ballotJ === null || ($ballotI !== null && $ballotI < $ballotJ)) {
                                    $preferenceMatrix[$iKey][$jKey] += 1;
                                }

                            } else {
                                // Original 'Max' version, which counts any no opinion as a no-decision between
                                // the two candidates.

                                // 'No opinion' doesn't count for either candidate being compared.
                                if ($ballotI === null || $ballotJ === null) {
                                    continue;
                                }
                                if ($ballotI < $ballotJ) {
                                    $preferenceMatrix[$iKey][$jKey] += 1;
                                }
                            }
                        }
                    }
                }

                // We order the candidates' defeats. Note that "defeats" really means all pairwise contests here.
                // Proper defeats result in negative values, while victories result in positive values.
                // We then sort them smallest to largest, so the worst defeat comes first.

                $defeats = [];
                for ($i = 0; $i < $nC; $i++) {
                    $iKey = $this->candidates[$i];
                    $candidateDefeats = [];
                    for ($j = 0; $j < $nC; $j++) {
                        $jKey = $this->candidates[$j];
                        if ($iKey === $jKey) {
                            continue;
                        }
                        $candidateDefeats[] = [
                            $preferenceMatrix[$iKey][$jKey] - $preferenceMatrix[$jKey][$iKey],  // Margin
                            $preferenceMatrix[$iKey][$jKey]                               // Winning Votes
                        ];
                    }

                    // Sort candidates' defeats by margin first, winning votes second.
                    // Note that this is not what the Darlington paper recommends, but what
                    // CIVS implements.
                    usort(
                        $candidateDefeats,
                        static function ($x, $y) {
                            if ($x[0] > $y[0]) {
                                return 1;
                            }
                            if ($x[0] < $y[0]) {
                                return -1;
                            }
                            return $x[1] <=> $y[1];
                        }
                    );

                    $defeats[$i] = $candidateDefeats;
                }

                $winners = $this->maxima($this->candidates, $defeats);

                // Do nothing if count($winners) === 0
                // That can happen if there are ties, and we have reached the end of the 'for $winnerRank' outer loop.

                if (count($winners) === 1) {
                    $winner = current($winners);
                    $key = array_search($winner, $this->candidates, true);
                    array_splice($this->candidates, $key, 1);
                    $results[] = new RankingResult($this->titles[$winner], $this->anilistIds[$winner], $winnerRank);
                } elseif (count($winners) > 1) {
                    foreach ($winners as $winner) {
                        $key = array_search($winner, $this->candidates, true);
                        $results[] = new RankingResult($this->titles[$winner], $this->anilistIds[$winner], $winnerRank);
                        array_splice($this->candidates, $key, 1);
                    }
                }
            }
            return $results;
        } catch (Exception $e) {
            print $e->getMessage();
            return [];
        }
    }
}
