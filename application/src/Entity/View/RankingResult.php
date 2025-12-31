<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Entity\View;

final class RankingResult
{
    private string $showTitle;

    private int $rank;

    private string $anilistId;

    public function __construct(?string $showTitle, ?string $anilistId, ?int $rank)
    {
        $this->showTitle = $showTitle ?? '(unknown)';
        $this->anilistId = $anilistId ?? '(unknown)';
        $this->rank = $rank ?? 0;
    }

    /**
     * @return string
     */
    public function getShowTitle(): string
    {
        return $this->showTitle;
    }

    /**
     * @return string
     */
    public function getAnilistId(): string
    {
        return $this->anilistId;
    }

    /**
     * @return int
     */
    public function getRank(): int
    {
        return $this->rank;
    }

    public function __toString(): string
    {
        return sprintf("%d: %s | %s", $this->getRank(), $this->getShowTitle(), $this->getAnilistId());
    }

    public function getShowCombinedTitle(): string
    {
        return $this->getShowTitle();
    }

    public function getRelatedShowNames(): ?string
    {
        return null;
    }

    public function jsonSerialize(): array
    {
        return [
            'showTitle' => $this->getShowTitle(),
            'showCombinedTitle' => $this->getShowCombinedTitle(),
            'anilistId' => $this->getAnilistId(),
            'rank' => $this->getRank(),
            'relatedShowNames' => $this->getRelatedShowNames(),
        ];
    }
}
