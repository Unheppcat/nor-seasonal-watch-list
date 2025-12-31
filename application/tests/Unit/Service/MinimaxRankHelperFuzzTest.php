<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\MinimaxRankHelper;
use Exception;
use PHPUnit\Framework\TestCase;

class MinimaxRankHelperFuzzTest extends TestCase
{
    public function setUp(): void
    {

    }

    public function tearDown(): void
    {

    }

    /**
     * @test
     */
    public function itCanConstruct(): void
    {
        $helper = new MinimaxRankHelper(false);
        static::assertNotNull($helper);
    }

    /**
     * @test
     */
    public function itCanRank(): void
    {
        $helper = new MinimaxRankHelper(false);
        $helper->setTitles($this->getTitles());
        $helper->setAnilistIds($this->getAnilistIds());
        $helper->setNumberOfWinners(count($this->getTitles()));
        foreach ($this->getBallots() as $ballot) {
            $helper->addBallot($ballot);
        }
        $ranks = $helper->getRanks();
        static::assertNotEmpty($ranks);
        static::assertEquals('Title 1', $ranks[0]->getShowTitle());
    }

    /**
     * @test
     * @throws Exception
     * @noinspection ForgottenDebugOutputInspection
     */
    public function withFuzzyValuesTest(): void
    {
        for ($i = 1; $i <= 5000; $i++) {
            $titleCount = random_int(5, 20);
            $ballotCount = random_int(2, 30);
            $values = $this->getFuzzyValues($titleCount);
            static::assertCount($titleCount, $values['titles']);
            $ballots = $this->getFuzzyBallots($titleCount, $ballotCount);
            static::assertCount($ballotCount, $ballots);
            $helper = new MinimaxRankHelper(false);
            $helper->setTitles($values['titles']);
            $helper->setAnilistIds($values['anilistIds']);
            $helper->setNumberOfWinners($titleCount);
            foreach ($ballots as $ballot) {
                $helper->addBallot($ballot);
            }
            $ranks = $helper->getRanks();
            if (empty($ranks)) {
                print("TEST FAILED!\nTitles:\n");
                print_r($values['titles']);
                print("\nBallots:\n");
                print_r($ballots);
            }
            static::assertNotEmpty($ranks);
        }
        static::assertTrue(true);
    }

    private function getTitles(): array
    {
        return [
            1 => 'Title 1',
            10 => 'Title 2',
            3 => 'Title 3',
            15 => 'Title 4',
            100 => 'Title 5',
            6 => 'Title 6',
            2 => 'Title 7',
            33 => 'Title 8',
            8 => 'Title 9',
            20 => 'Title 10',

        ];
    }

    /**
     * @param int $titleCount
     * @return array
     * @throws Exception
     */
    private function getFuzzyValues(int $titleCount): array
    {
        $titles = [];
        $anilistIds = [];
        for ($i = 1; $i <= $titleCount; $i++) {
            $randomKey = random_int(1, 10000);
            while (isset($titles[$randomKey])) {
                $randomKey = random_int(1, 10000);
            }
            $titles[$randomKey] = sprintf("Title %d", $i);
            $anilistIds[$randomKey] = $randomKey;
        }
        return [ 'titles' => $titles, 'anilistIds' => $anilistIds ];
    }

    private function getBallots(): array
    {
        return array_fill(1, 8, ["1", "1", "3", "2", "4", "10", "7", "6", "No opinion", "No opinion"]);
    }

    /**
     * @param int $numberOfTitles
     * @param int $numberOfBallots
     * @return array
     * @throws Exception
     */
    private function getFuzzyBallots(int $numberOfTitles, int $numberOfBallots): array
    {
        $ballots = [];
        for ($n = 1; $n <= $numberOfBallots; $n++) {
            $ballot = [];
            $noOpinionChance = random_int(1, 4);
            $worstOpinionChance = random_int(1, 4);
            for ($i = 1; $i <= $numberOfTitles; $i++) {
                $rank = (string)random_int(1, $numberOfTitles);
                $rank = random_int(1, $noOpinionChance) === 1 ? 'No opinion' : $rank;
                $rank = random_int(1, $worstOpinionChance) === 1 ? (string)$numberOfTitles : $rank;
                $ballot[] = $rank;
            }
            shuffle($ballot);
            $ballots[] = $ballot;
        }
        return $ballots;
    }

    private function getAnilistIds(): array
    {
        return [
            1 =>   '1111111',
            10 =>  '2222222',
            3 =>   '3333333',
            15 =>  '4444444',
            100 => '5555555',
            6 =>   '6666666',
            2 =>   '7777777',
            33 =>  '8888888',
            8 =>   '9999999',
            20 =>  '0000000',

        ];
    }
}
