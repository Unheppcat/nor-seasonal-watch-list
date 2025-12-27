<?php /** @noinspection UnknownInspectionInspection */

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\ElectionShowBuffRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ElectionShowBuffRepository::class)]
class ElectionShowBuff
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = 0;

    /**
     * @var Election|null
     */
    #[ORM\ManyToOne(targetEntity: Election::class, inversedBy: 'electionShowBuffs')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Election $election = null;

    /**
     * @var Show|null
     */
    #[ORM\ManyToOne(targetEntity: Show::class, inversedBy: 'electionShowBuffs')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Show $animeShow = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $buffRule = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Election|null
     */
    public function getElection(): ?Election
    {
        return $this->election;
    }

    /**
     * @param Election|null $election
     * @return $this
     */
    public function setElection(?Election $election): self
    {
        $this->election = $election;

        return $this;
    }

    /**
     * @return Show|null
     */
    public function getAnimeShow(): ?Show
    {
        return $this->animeShow;
    }

    /**
     * @param Show|null $animeShow
     * @return $this
     */
    public function setAnimeShow(?Show $animeShow): self
    {
        $this->animeShow = $animeShow;

        return $this;
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
     * @return $this
     */
    public function setBuffRule(?string $buffRule): self
    {
        $this->buffRule = $buffRule;

        return $this;
    }
}
