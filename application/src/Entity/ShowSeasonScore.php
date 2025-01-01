<?php /** @noinspection PhpPropertyOnlyWrittenInspection */
/** @noinspection UnknownInspectionInspection */

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\ShowSeasonScoreRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="show_season_score",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="show_season_score_unique",
 *             columns={"season_id", "show_id", "user_id"}
 *         )
 *     }
 * )
 * @ORM\Entity(repositoryClass=ShowSeasonScoreRepository::class)
 */
class ShowSeasonScore
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @var Activity|null
     * @ORM\ManyToOne(targetEntity=Activity::class, inversedBy="scores")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Activity $activity = null;

    /**
     * @var Score|null
     * @ORM\ManyToOne(targetEntity=Score::class, inversedBy="showSeasonScores")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Score $score = null;

    /**
     * @var Season|null
     * @ORM\ManyToOne(targetEntity=Season::class, inversedBy="showSeasonScores")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Season $season;

    /**
     * @var Show|null
     * @ORM\ManyToOne(targetEntity=Show::class, inversedBy="scores")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Show $show;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="showSeasonScores")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Activity|null
     */
    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    /**
     * @param Activity|null $activity
     */
    public function setActivity(?Activity $activity): void
    {
        $this->activity = $activity;
    }

    /**
     * @return Score|null
     */
    public function getScore(): ?Score
    {
        return $this->score;
    }

    /**
     * @param Score|null $score
     */
    public function setScore(?Score $score): void
    {
        $this->score = $score;
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
     * @return Show|null
     */
    public function getShow(): ?Show
    {
        return $this->show;
    }

    /**
     * @param Show $show
     * @return $this
     */
    public function setShow(Show $show): self
    {
        $this->show = $show;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'activity' => $this->getActivity() ? $this->getActivity()->jsonSerialize() : [],
            'score' => $this->getScore() ? $this->getScore()->jsonSerialize() : [],
            'season' => $this->getSeason() ? $this->getSeason()->jsonSerialize() : [],
            'show' => $this->getShow() ? $this->getShow()->jsonSerialize() : [],
            'user' => $this->getUser() ? $this->getUser()->jsonSerialize() : [],
        ];
    }

    public function jsonSerializeForWatch(): array
    {
        return [
            'id' => $this->getId(),
            'activity' => $this->getActivity() ? $this->getActivity()->jsonSerialize() : [],
            'recommendation' => $this->getScore() ? $this->getScore()->jsonSerialize() : [],
            'user' => $this->getUser() ? $this->getUser()->jsonSerialize() : [],
        ];
    }

    public function jsonSerializeForWatchView(): array
    {
        return [
            'show' => $this->getShow() ? $this->getShow()->jsonSerializeForWatchView() : [],
        ];
    }
}
