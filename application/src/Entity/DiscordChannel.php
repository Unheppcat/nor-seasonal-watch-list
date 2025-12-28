<?php
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\DiscordChannelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DiscordChannelRepository::class)]
class DiscordChannel
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
     * @var Season
     */
    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'discordChannels')]
    #[ORM\JoinColumn(nullable: false)]
    private Season $season;

    /**
     * @var Show
     */
    #[ORM\OneToOne(inversedBy: 'discordChannel', targetEntity: Show::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private Show $animeShow;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private bool $hidden = false;

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
     * @return Season
     */
    public function getSeason(): Season
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
     * @return Show
     */
    public function getShow(): Show
    {
        return $this->animeShow;
    }

    /**
     * @return Show
     */
    public function getAnimeShow(): Show
    {
        return $this->getShow();
    }

    /**
     * @param Show $animeShow
     * @return $this
     */
    public function setShow(Show $animeShow): self
    {
        $this->animeShow = $animeShow;

        return $this;
    }

    /**
     * @param Show $animeShow
     * @return $this
     */
    public function setAnimeShow(Show $animeShow): self
    {
        return $this->setShow($animeShow);
    }

    /**
     * @return bool
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     * @return $this
     */
    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
