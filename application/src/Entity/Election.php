<?php /** @noinspection PhpPropertyOnlyWrittenInspection */
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\ElectionRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use RuntimeException;

#[ORM\Entity(repositoryClass: ElectionRepository::class)]
class Election
{
    public const SIMPLE_ELECTION = 'simple';
    public const RANKED_CHOICE_ELECTION = 'ranked-choice';

    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $title = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var Season|null
     */
    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'elections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Season $season;

    /**
     * @var DateTime|null
     */
    #[ORM\Column(type: 'datetime')]
    private ?DateTime $startDate;

    /**
     * @var DateTime|null
     */
    #[ORM\Column(type: 'datetime')]
    private ?DateTime $endDate;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxVotes = null;

    #[ORM\OneToMany(mappedBy: 'election', targetEntity: ElectionVote::class, cascade: ['persist', 'remove'])]
    private Collection $electionVotes;

    #[ORM\OneToMany(mappedBy: 'election', targetEntity: ElectionShowBuff::class, cascade: ['persist', 'remove'])]
    private Collection $electionShowBuffs;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string')]
    private string $electionType = '';

    /**
     * Election constructor.
     */
    public function __construct()
    {
        $this->electionVotes = new ArrayCollection();
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
     * @return Season|null
     */
    public function getSeason(): ?Season
    {
        return $this->season;
    }

    /**
     * @param Season $season
     * @return $this
     */
    public function setSeason(Season $season): self
    {
        $this->season = $season;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     * @return $this
     */
    public function setStartDate(DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    /**
     * @param DateTime $endDate
     * @return $this
     */
    public function setEndDate(DateTime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        try {
            $now = new DateTime();
            return $this->getStartDate() && $this->getEndDate() &&
                $now >= $this->getStartDate() && $now <= $this->getEndDate();
        } /** @noinspection PhpUnusedLocalVariableInspection */ catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return Collection
     */
    public function getElectionVotes(): Collection
    {
        return $this->electionVotes;
    }

    /**
     * @param ElectionVote $showSeasonVote
     * @return $this
     */
    public function addElectionVote(ElectionVote $showSeasonVote): self
    {
        if (!$this->electionVotes->contains($showSeasonVote)) {
            $this->electionVotes[] = $showSeasonVote;
            $showSeasonVote->setElection($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        $start = $this->getStartDate() ? $this->getStartDate()->format(' \(Y-m-d H:i:s\)') : ' (unknown start)';
        return $this->getName() . $start;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return $this
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
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
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getName(): string
    {
        if ($this->getTitle() !== null) {
            return $this->getTitle();
        }
        if ($this->getSeason() !== null) {
            return $this->getSeason()->getName();
        }
        return '(not set)';
    }

    /**
     * @return int|null
     */
    public function getMaxVotes(): ?int
    {
        return $this->maxVotes;
    }

    /**
     * @param int|null $maxVotes
     * @return $this
     */
    public function setMaxVotes(?int $maxVotes): self
    {
        $this->maxVotes = $maxVotes;
        return $this;
    }

    /**
     * @return Collection
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
            $electionShowBuff->setElection($this);
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
            if ($electionShowBuff->getElection() === $this) {
                $electionShowBuff->setElection(null);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getElectionType(): string
    {
        return $this->electionType ?? '(unknown)';
    }

    /**
     * @param string $electionType
     */
    public function setElectionType(string $electionType): void
    {
        $validTypes = [
            self::SIMPLE_ELECTION,
            self::RANKED_CHOICE_ELECTION,
        ];
        if (!in_array($electionType, $validTypes)) {
            throw new RuntimeException($electionType . " is not a valid election type.");
        }
        $this->electionType = $electionType;
    }
}
