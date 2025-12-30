<?php
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

namespace App\Entity\View;

class VoteTally
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
     * @var int $voteCount
     */
    private int $voteCount = 0;

    /**
     * @var string|null $buffRule
     */
    private ?string $buffRule = null;

    /**
     * @var int $buffedVoteCount
     */
    private int $buffedVoteCount = 0;

    /**
     * @var float $votePercentOfTotal
     */
    private float $votePercentOfTotal = 0.0;

    /**
     * @var float $buffedVotePercentOfTotal
     */
    private float $buffedVotePercentOfTotal = 0.0;

    /**
     * @var float $votePercentOfVoterTotal
     */
    private float $votePercentOfVoterTotal = 0.0;

    /**
     * @var float $buffedVotePercentOfVoterTotal
     */
    private float $buffedVotePercentOfVoterTotal = 0.0;

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
     * @return int
     */
    public function getVoteCount(): int
    {
        return $this->voteCount;
    }

    /**
     * @param int $voteCount
     */
    public function setVoteCount(int $voteCount): void
    {
        $this->voteCount = $voteCount;
    }

    /**
     * @return float
     */
    public function getVotePercentOfTotal(): float
    {
        return $this->votePercentOfTotal;
    }

    /**
     * @param float $votePercentOfTotal
     */
    public function setVotePercentOfTotal(float $votePercentOfTotal): void
    {
        $this->votePercentOfTotal = $votePercentOfTotal;
    }

    /**
     * @return float
     */
    public function getVotePercentOfVoterTotal(): float
    {
        return $this->votePercentOfVoterTotal;
    }

    /**
     * @param float $votePercentOfVoterTotal
     */
    public function setVotePercentOfVoterTotal(float $votePercentOfVoterTotal): void
    {
        $this->votePercentOfVoterTotal = $votePercentOfVoterTotal;
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
     * @return string|null
     */
    public function getBuffRule(): ?string
    {
        return $this->buffRule;
    }

    /**
     * @param string|null $buffRule
     */
    public function setBuffRule(?string $buffRule): void
    {
        $this->buffRule = $buffRule;
    }

    /**
     * @return int
     */
    public function getBuffedVoteCount(): int
    {
        return $this->buffedVoteCount;
    }

    /**
     * @param int $buffedVoteCount
     */
    public function setBuffedVoteCount(int $buffedVoteCount): void
    {
        $this->buffedVoteCount = $buffedVoteCount;
    }

    /**
     * @return float
     */
    public function getBuffedVotePercentOfTotal(): float
    {
        return $this->buffedVotePercentOfTotal;
    }

    /**
     * @param float $buffedVotePercentOfTotal
     */
    public function setBuffedVotePercentOfTotal(float $buffedVotePercentOfTotal): void
    {
        $this->buffedVotePercentOfTotal = $buffedVotePercentOfTotal;
    }

    /**
     * @return float
     */
    public function getBuffedVotePercentOfVoterTotal(): float
    {
        return $this->buffedVotePercentOfVoterTotal;
    }

    /**
     * @param float $buffedVotePercentOfVoterTotal
     */
    public function setBuffedVotePercentOfVoterTotal(float $buffedVotePercentOfVoterTotal): void
    {
        $this->buffedVotePercentOfVoterTotal = $buffedVotePercentOfVoterTotal;
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
            'voteCount' => $this->getVoteCount(),
            'buffRule' => $this->getBuffRule(),
            'buffedVoteCount' => $this->getBuffedVoteCount(),
            'votePercentOfTotal' => $this->getVotePercentOfTotal(),
            'buffedVotePercentOfTotal' => $this->getBuffedVotePercentOfTotal(),
            'votePercentOfVoterTotal' => $this->getVotePercentOfVoterTotal(),
            'buffedVotePercentOfVoterTotal' => $this->getBuffedVotePercentOfVoterTotal(),
        ];
    }

}
