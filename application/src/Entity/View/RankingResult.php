<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace App\Entity\View;

final class RankingResult
{
    private string $showTitle;

    private int $rank;

    public function __construct(?string $showTitle, ?int $rank)
    {
        $this->showTitle = $showTitle ?? '(unknown)';
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
     * @return int
     */
    public function getRank(): int
    {
        return $this->rank;
    }

    public function __toString(): string
    {
        return sprintf("%d: %s", $this->getRank(), $this->getShowTitle());
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
            'rank' => $this->getRank(),
            'relatedShowNames' => $this->getRelatedShowNames(),
        ];
    }
}
