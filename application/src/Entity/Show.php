<?php /** @noinspection PhpPropertyOnlyWrittenInspection */
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpUnusedAliasInspection */

namespace App\Entity;

use App\Repository\ShowRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ShowRepository::class)]
#[ORM\Table(name: 'anime_show')]
#[UniqueEntity(fields: 'anilistId', message: 'That Anilist ID is already taken')]
class Show
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $japaneseTitle = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $englishTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $fullJapaneseTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $fullEnglishTitle = null;

    /**
     * @var Collection|Season[]
     */
    #[ORM\ManyToMany(targetEntity: Season::class, inversedBy: 'shows')]
    #[OrderBy(['rankOrder' => 'ASC'])]
    private Collection $seasons;

    /**
     * @var Collection|ShowSeasonScore[]
     */
    #[ORM\OneToMany(targetEntity: ShowSeasonScore::class, mappedBy: 'show', cascade: ['persist', 'remove'])]
    private Collection $scores;

    /**
     * @var Collection|ElectionVote[]
     */
    #[ORM\OneToMany(targetEntity: ElectionVote::class, mappedBy: 'animeShow', cascade: ['persist', 'remove'])]
    private Collection $votes;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $anilistId = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $hashtag = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $coverImageMedium = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $coverImageLarge = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $siteUrl = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $synonyms = null;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'exclude_from_elections', type: 'boolean', nullable: true)]
    private ?bool $excludeFromElections = null;

    /**
     * @var DiscordChannel|null
     */
    #[ORM\OneToOne(targetEntity: DiscordChannel::class, mappedBy: 'animeShow', cascade: ['persist', 'remove'])]
    #[JoinColumn(nullable: true)]
    private ?DiscordChannel $discordChannel = null;

    /**
     * @var Collection|Show[]
     */
    #[ORM\OneToMany(targetEntity: Show::class, mappedBy: 'firstShow', cascade: ['persist', 'detach'])]
    private Collection $relatedShows;

    /**
     * @var Show|null
     */
    #[ORM\ManyToOne(targetEntity: Show::class, inversedBy: 'relatedShows', cascade: ['persist'])]
    private ?Show $firstShow = null;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $malId = null;

    #[ORM\OneToMany(targetEntity: ElectionShowBuff::class, mappedBy: 'animeShow', cascade: ['persist', 'remove'])]
    private Collection $electionShowBuffs;

    public function __construct()
    {
        $this->seasons = new ArrayCollection();
        $this->scores = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->relatedShows = new ArrayCollection();
        $this->electionShowBuffs = new ArrayCollection();
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
    public function getJapaneseTitle(): ?string
    {
        return $this->japaneseTitle;
    }

    /**
     * @param string|null $japaneseTitle
     * @return $this
     */
    public function setJapaneseTitle(?string $japaneseTitle): self
    {
        $this->japaneseTitle = $japaneseTitle;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEnglishTitle(): ?string
    {
        return $this->englishTitle;
    }

    /**
     * @param string|null $englishTitle
     * @return $this
     */
    public function setEnglishTitle(?string $englishTitle): self
    {
        $this->englishTitle = $englishTitle;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFullJapaneseTitle(): ?string
    {
        return $this->fullJapaneseTitle;
    }

    /**
     * @param string|null $fullJapaneseTitle
     * @return $this
     */
    public function setFullJapaneseTitle(?string $fullJapaneseTitle): self
    {
        $this->fullJapaneseTitle = $fullJapaneseTitle;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFullEnglishTitle(): ?string
    {
        return $this->fullEnglishTitle;
    }

    /**
     * @param string|null $fullEnglishTitle
     * @return $this
     */
    public function setFullEnglishTitle(?string $fullEnglishTitle): self
    {
        $this->fullEnglishTitle = $fullEnglishTitle;

        return $this;
    }

    public function getAllTitles(): ?string
    {
        $result = [];
        if (!empty($this->japaneseTitle)) {
            $result[] = $this->japaneseTitle;
        }
        if (!empty($this->fullJapaneseTitle)) {
            $result[] = $this->fullJapaneseTitle;
        }
        if (!empty($this->englishTitle)) {
            $result[] = $this->englishTitle;
        }
        return empty($result) ? null : implode('<br>', $result);
    }

    public function getTitlesForSelect(): ?string
    {
        $result = [];
        if (!empty($this->japaneseTitle)) {
            $result[] = $this->japaneseTitle;
        }
        if (!empty($this->englishTitle)) {
            $result[] = $this->englishTitle;
        }
        return empty($result) ? null : implode(' | ', $result);
    }

    public function getVoteStyleTitles(): ?string
    {
        $result = '';
        if (!empty($this->japaneseTitle)) {
            $result .= $this->japaneseTitle . ' ';
        }
        if (!empty($this->fullJapaneseTitle)) {
            $result .= '(' . $this->fullJapaneseTitle . ') ';
        }
        if (!empty($this->englishTitle)) {
            $result .= $this->englishTitle;
        }
        if (empty($result)) {
            return null;
        }
        return trim($result);
    }

    public function getAllShortTitles(): ?string
    {
        $result = [];
        if (!empty($this->japaneseTitle)) {
            $result[] = $this->japaneseTitle;
        }
        if (!empty($this->fullJapaneseTitle)) {
            $result[] = $this->fullJapaneseTitle;
        }
        return empty($result) ? null : implode(' / ', $result);
    }

    /**
     * @return Collection|Season[]
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    /**
     * @param Season $season
     * @return $this
     */
    public function addSeason(Season $season): self
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons[] = $season;
        }

        return $this;
    }

    /**
     * @param Season $season
     * @return $this
     */
    public function removeSeason(Season $season): self
    {
        $this->seasons->removeElement($season);

        return $this;
    }

    /**
     * @return Collection|ShowSeasonScore[]
     */
    public function getScores()
    {
        return $this->scores;
    }

    /**
     * @param ShowSeasonScore $score
     * @return $this
     */
    public function addScore(ShowSeasonScore $score): self
    {
        if (!$this->scores->contains($score)) {
            $this->scores[] = $score;
        }

        return $this;
    }

    /**
     * @param ShowSeasonScore $score
     * @return $this
     */
    public function removeScore(ShowSeasonScore $score): self
    {
        $this->scores->removeElement($score);

        return $this;
    }

    /**
     * @return Collection|ElectionVote[]
     */
    public function getVotes()
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
     * @return string|null
     */
    public function getAnilistId(): ?string
    {
        return $this->anilistId;
    }

    /**
     * @param string|null $anilistId
     * @return $this
     */
    public function setAnilistId(?string $anilistId): self
    {
        $this->anilistId = $anilistId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getHashtag(): ?string
    {
        return $this->hashtag;
    }

    /**
     * @param string|null $hashtag
     */
    public function setHashtag(?string $hashtag): void
    {
        $this->hashtag = $hashtag;
    }

    /**
     * @return string|null
     */
    public function getCoverImageMedium(): ?string
    {
        return $this->coverImageMedium;
    }

    /**
     * @param string|null $coverImageMedium
     */
    public function setCoverImageMedium(?string $coverImageMedium): void
    {
        $this->coverImageMedium = $coverImageMedium;
    }

    /**
     * @return string|null
     */
    public function getCoverImageLarge(): ?string
    {
        return $this->coverImageLarge;
    }

    /**
     * @param string|null $coverImageLarge
     */
    public function setCoverImageLarge(?string $coverImageLarge): void
    {
        $this->coverImageLarge = $coverImageLarge;
    }

    /**
     * @return string|null
     */
    public function getSiteUrl(): ?string
    {
        return $this->siteUrl;
    }

    /**
     * @param string|null $siteUrl
     */
    public function setSiteUrl(?string $siteUrl): void
    {
        $this->siteUrl = $siteUrl;
    }

    /**
     * @return string|null
     */
    public function getSynonyms(): ?string
    {
        return $this->synonyms;
    }

    /**
     * @param string|null $synonyms
     */
    public function setSynonyms(?string $synonyms): void
    {
        $this->synonyms = $synonyms;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getJapaneseTitle() . ' (' . $this->getEnglishTitle() . ')';
    }

    /**
     * @return DiscordChannel|null
     */
    public function getDiscordChannel(): ?DiscordChannel
    {
        return $this->discordChannel;
    }

    /**
     * @param DiscordChannel|null $discordChannel
     * @return $this
     */
    public function setDiscordChannel(?DiscordChannel $discordChannel): self
    {
        // set the owning side of the relation if necessary
        if ($discordChannel && $discordChannel->getShow() !== $this) {
            $discordChannel->setShow($this);
        }

        $this->discordChannel = $discordChannel;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getExcludeFromElections(): ?bool
    {
        return $this->excludeFromElections;
    }

    /**
     * @param bool|null $excludeFromElections
     */
    public function setExcludeFromElections(?bool $excludeFromElections): void
    {
        $this->excludeFromElections = $excludeFromElections;
    }

    /**
     * @return int|null
     */
    public function getMalId(): ?int
    {
        return $this->malId;
    }

    /**
     * @param int|null $malId
     */
    public function setMalId(?int $malId): void
    {
        $this->malId = $malId;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'japaneseTitle' => $this->getJapaneseTitle(),
            'englishTitle' => $this->getEnglishTitle(),
            'fullJapaneseTitle' => $this->getFullJapaneseTitle(),
            'anilistId' => $this->getAnilistId(),
            'malId' => $this->getMalId(),
            'description' => $this->getDescription(),
            'hashtag' => $this->getHashtag(),
            'coverImageMedium' => $this->getCoverImageMedium(),
            'coverImageLarge' => $this->getCoverImageLarge(),
            'siteUrl' => $this->getSiteUrl(),
            'synonyms' => $this->getSynonyms(),
            'excludeFromElections' => $this->getExcludeFromElections(),
        ];
    }

    /**
     * @return Show[]|Collection
     */
    public function getRelatedShows(): Collection
    {
        return $this->relatedShows;
    }

    /**
     * @param Collection $relatedShows
     * @return self
     */
    public function setRelatedShows(Collection $relatedShows): self
    {
        $this->relatedShows->clear();
        $relatedShows->map(function ($show) { $this->relatedShows->add($show); });
        return $this;
    }

    public function addRelatedShow(Show $show): self
    {
        if (!$this->relatedShows->contains($show)) {
            $show->setFirstShow($this);
            $this->relatedShows[] = $show;
        }

        return $this;
    }

    public function removeRelatedShow(Show $show): self
    {
        $this->relatedShows->removeElement($show);
        $show->setFirstShow(null);

        return $this;
    }

    /**
     * @return Show|null
     */
    public function getFirstShow(): ?Show
    {
        return $this->firstShow;
    }

    /**
     * @param Show|null $firstShow
     * @return self
     */
    public function setFirstShow(?Show $firstShow): self
    {
        $this->firstShow = $firstShow;
        return $this;
    }

    /**
     * @return Collection|ElectionShowBuff[]
     */
    public function getElectionShowBuffs(): Collection
    {
        return $this->electionShowBuffs;
    }

    /**
     * @param ElectionShowBuff $electionShowBuff
     * @return $this
     */
    public function addElectionShowBuff(ElectionShowBuff $electionShowBuff): self
    {
        if (!$this->electionShowBuffs->contains($electionShowBuff)) {
            $this->electionShowBuffs[] = $electionShowBuff;
            $electionShowBuff->setAnimeShow($this);
        }

        return $this;
    }

    /**
     * @param ElectionShowBuff $electionShowBuff
     * @return $this
     */
    public function removeElectionShowBuff(ElectionShowBuff $electionShowBuff): self
    {
        if ($this->electionShowBuffs->removeElement($electionShowBuff)) {
            // set the owning side to null (unless already changed)
            /** @noinspection NestedPositiveIfStatementsInspection */
            if ($electionShowBuff->getAnimeShow() === $this) {
                $electionShowBuff->setAnimeShow(null);
            }
        }

        return $this;
    }
}
