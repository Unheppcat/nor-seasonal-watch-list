<?php /** @noinspection PhpPropertyOnlyWrittenInspection */
/** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnusedAliasInspection */
/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\SeasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonException;

#[ORM\Entity(repositoryClass: SeasonRepository::class)]
class Season
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $year = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $yearPart = null;

    #[ORM\Column(type: 'integer')]
    private ?int $rankOrder = null;

    #[ORM\ManyToMany(targetEntity: Show::class, mappedBy: 'seasons')]
    private Collection $shows;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(mappedBy: 'season', targetEntity: Election::class, cascade: ['persist', 'remove'])]
    private Collection $elections;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(mappedBy: 'season', targetEntity: ElectionVote::class, cascade: ['persist', 'remove'])]
    private Collection $votes;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(mappedBy: 'season', targetEntity: ShowSeasonScore::class, cascade: ['persist', 'remove'])]
    private Collection $showSeasonScores;

    /**
     * @var ArrayCollection|Collection
     */
    #[ORM\OneToMany(mappedBy: 'season', targetEntity: DiscordChannel::class, cascade: ['persist', 'remove'])]
    private Collection|ArrayCollection $discordChannels;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private bool $hiddenFromSeasonsList = false;

    public function __construct()
    {
        $this->shows = new ArrayCollection();
        $this->elections = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->discordChannels = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
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
     * @return int|null
     */
    public function getYear(): ?int
    {
        return $this->year;
    }

    /**
     * @param int|null $year
     * @return $this
     */
    public function setYear(?int $year): self
    {
        $this->year = $year;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getYearPart(): ?string
    {
        return $this->yearPart;
    }

    /**
     * @param string|null $yearPart
     * @return $this
     */
    public function setYearPart(?string $yearPart): self
    {
        $this->yearPart = $yearPart;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getRankOrder(): ?int
    {
        return $this->rankOrder;
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
     * @return Collection
     */
    public function getShows(): Collection
    {
        return $this->shows;
    }

    /**
     * @param Show $show
     * @return $this
     */
    public function addShow(Show $show): self
    {
        if (!$this->shows->contains($show)) {
            $this->shows[] = $show;
            $show->addSeason($this);
        }

        return $this;
    }

    /**
     * @param Show $show
     * @return $this
     */
    public function removeShow(Show $show): self
    {
        if ($this->shows->removeElement($show)) {
            $show->removeSeason($this);
        }

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
     * @return Collection
     */
    public function getElections(): Collection
    {
        return $this->elections;
    }

    /**
     * @param Election $election
     * @return $this
     */
    public function addElection(Election $election): self
    {
        if (!$this->elections->contains($election)) {
            $this->elections[] = $election;
            $election->setSeason($this);
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    /**
     * @param ElectionVote $vote
     * @return $this
     */
    public function addVote(ElectionVote $vote): self
    {
        if (!$this->votes->contains($vote)) {
            $this->votes[] = $vote;
            $vote->setSeason($this);
        }

        return $this;
    }

    /**
     * @param ElectionVote $vote
     * @return $this
     */
    public function removeVote(ElectionVote $vote): self
    {
        $this->votes->removeElement($vote);
        return $this;
    }


    /**
     * @return Collection
     */
    public function getDiscordChannels(): Collection
    {
        return $this->discordChannels;
    }

    /**
     * @param DiscordChannel $discordChannel
     * @return $this
     */
    public function addDiscordChannel(DiscordChannel $discordChannel): self
    {
        if (!$this->discordChannels->contains($discordChannel)) {
            $this->discordChannels[] = $discordChannel;
            $discordChannel->setSeason($this);
        }

        return $this;
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'year' => $this->getYear(),
            'yearPart' => $this->getYearPart(),
            'rankOrder' => $this->getRankOrder(),
        ];
    }

    /**
     * @return Collection
     */
    public function getShowSeasonScores(): Collection
    {
        return $this->showSeasonScores;
    }

    /**
     * @param Collection|ShowSeasonScore[] $showSeasonScores
     */
    public function setShowSeasonScores(Collection|array $showSeasonScores): void
    {
        $this->showSeasonScores = $showSeasonScores;
    }

    /**
     * @return bool
     */
    public function isHiddenFromSeasonsList(): bool
    {
        return $this->hiddenFromSeasonsList;
    }

    /**
     * @param bool $hiddenFromSeasonsList
     * @return $this
     */
    public function setHiddenFromSeasonsList(bool $hiddenFromSeasonsList): self
    {
        $this->hiddenFromSeasonsList = $hiddenFromSeasonsList;
        return $this;
    }
}
