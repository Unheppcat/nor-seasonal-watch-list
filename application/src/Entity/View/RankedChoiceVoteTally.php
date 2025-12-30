<?php
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

namespace App\Entity\View;

class RankedChoiceVoteTally
{
    /**
     * @var int $id
     */
    private int $id = 0;

    /**
     * @var string $showJapaneseTitle
     */
    private string $showJapaneseTitle = '';

    /**
     * @var string $showEnglishTitle
     */
    private string $showEnglishTitle = '';

    /**
     * @var string $showFullJapaneseTitle
     */
    private string $showFullJapaneseTitle = '';

    /**
     * @var string
     */
    private string $showCombinedTitle = '';

    /**
     * @var array
     */
    private array $relatedShowNames = [];

    /**
     * @var int $showId
     */
    private int $showId = 0;

    /**
     * @var int $finalRank
     */
    private int $finalRank = 0;

    /**
     * @var string $rankStats
     */
    private string $rankStats = '';

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getShowJapaneseTitle(): string
    {
        return $this->showJapaneseTitle;
    }

    /**
     * @param string $showJapaneseTitle
     */
    public function setShowJapaneseTitle(string $showJapaneseTitle): void
    {
        $this->showJapaneseTitle = $showJapaneseTitle;
    }

    /**
     * @return string
     */
    public function getShowEnglishTitle(): string
    {
        return $this->showEnglishTitle;
    }

    /**
     * @param string $showEnglishTitle
     */
    public function setShowEnglishTitle(string $showEnglishTitle): void
    {
        $this->showEnglishTitle = $showEnglishTitle;
    }

    /**
     * @return string
     */
    public function getShowFullJapaneseTitle(): string
    {
        return $this->showFullJapaneseTitle;
    }

    /**
     * @param string $showFullJapaneseTitle
     */
    public function setShowFullJapaneseTitle(string $showFullJapaneseTitle): void
    {
        $this->showFullJapaneseTitle = $showFullJapaneseTitle;
    }

    /**
     * @return array
     */
    public function getRelatedShowNames(): array
    {
        return $this->relatedShowNames;
    }

    /**
     * @param array $relatedShowNames
     */
    public function setRelatedShowNames(array $relatedShowNames): void
    {
        $this->relatedShowNames = $relatedShowNames;
    }

    /**
     * @param string $name
     */
    public function addRelatedShowName(string $name): void
    {
        $this->relatedShowNames[] = $name;
    }

    /**
     * @return int
     */
    public function getShowId(): int
    {
        return $this->showId;
    }

    /**
     * @param int $showId
     */
    public function setShowId(int $showId): void
    {
        $this->showId = $showId;
    }

    /**
     * @return string
     */
    public function getShowCombinedTitle(): string
    {
        return $this->showCombinedTitle;
    }

    /**
     * @param string $showCombinedTitle
     */
    public function setShowCombinedTitle(string $showCombinedTitle): void
    {
        $this->showCombinedTitle = $showCombinedTitle;
    }

    /**
     * @return int
     */
    public function getFinalRank(): int
    {
        return $this->finalRank;
    }

    public function getRank(): int
    {
        return $this->finalRank;
    }

    /**
     * @param int $finalRank
     */
    public function setFinalRank(int $finalRank): void
    {
        $this->finalRank = $finalRank;
    }

    /**
     * @return string
     */
    public function getRankStats(): string
    {
        return $this->rankStats;
    }

    /**
     * @param string $rankStats
     */
    public function setRankStats(string $rankStats): void
    {
        $this->rankStats = $rankStats;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'showId' => $this->getShowId(),
            'showJapaneseTitle' => $this->getShowJapaneseTitle(),
            'showEnglishTitle' => $this->getShowEnglishTitle(),
            'showFullJapaneseTitle' => $this->getShowFullJapaneseTitle(),
            'showCombinedTitle' => $this->getShowCombinedTitle(),
            'relatedShowNames' => $this->getRelatedShowNames(),
            'rank' => $this->getRank(),
            'rankStats' => $this->getRankStats(),
        ];
    }
}
