<?php /** @noinspection PhpPropertyOnlyWrittenInspection */
/** @noinspection DuplicatedCode */
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
class Activity
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $name = '';

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 50)]
    private string $nickname = '';

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    private int $rankOrder = 0;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 3, nullable: true)]
    private ?string $value = null;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 50)]
    private string $colorValue = '';

    /**
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private string $icon = '';

    /**
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private string $slug = '';

    /**
     * @var Collection|ShowSeasonScore[]
     */
    #[ORM\OneToMany(targetEntity: ShowSeasonScore::class, mappedBy: 'activity', cascade: ['persist', 'remove'])]
    private Collection $scores;

    /**
     * @return ShowSeasonScore[]|Collection
     */
    public function getScores(): Collection
    {
        return $this->scores;
    }

    /**
     * @param ShowSeasonScore[]|Collection $scores
     */
    public function setScores(Collection $scores): void
    {
        $this->scores->clear();
        $scores->map(function ($score) { $this->scores->add($score); });
    }

    public function __construct()
    {
        $this->scores = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getRankOrder(): int
    {
        return $this->rankOrder ?? 0;
    }

    /**
     * @param int $rankOrder
     * @return $this
     */
    public function setRankOrder(int $rankOrder): self
    {
        $this->rankOrder = $rankOrder;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     * @return $this
     */
    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getNickname(): string
    {
        return $this->nickname ?? '';
    }

    /**
     * @param string $nickname
     */
    public function setNickname(string $nickname): void
    {
        $this->nickname = $nickname;
    }

    /**
     * @return string
     */
    public function getColorValue(): string
    {
        return $this->colorValue ?? '';
    }

    /**
     * @param string $colorValue
     */
    public function setColorValue(string $colorValue): void
    {
        $this->colorValue = $colorValue;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon ?? '';
    }

    /**
     * @param string $icon
     */
    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug ?? '';
    }

    /**
     * @param string $slug
     */
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'nickname' => $this->getNickname(),
            'rankOrder' => $this->getRankOrder(),
            'value' => $this->getValue(),
            'colorValue' => $this->getColorValue(),
            'icon' => $this->getIcon(),
            'slug' => $this->getSlug(),
        ];
    }
}
