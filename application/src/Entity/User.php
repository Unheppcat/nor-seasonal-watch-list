<?php /** @noinspection PhpPropertyOnlyWrittenInspection */
/** @noinspection PhpPrivateFieldCanBeLocalVariableInspection */
/** @noinspection UnknownInspectionInspection */

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['apiKey'], message: 'This api key is already in use.', ignoreNull: true)]
class User implements UserInterface
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
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $username = '';

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $displayName = null;

    /**
     * @var array
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $oauth2state = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $discordUsername = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $discordDiscriminator = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $discordId = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $discordAvatar = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $discordLocal = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $discordToken = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $discordRefreshToken;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $discordTokenExpires = null;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: ShowSeasonScore::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $showSeasonScores;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: ElectionVote::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $electionVotes;

    /**
     * @var array|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $prefsStore = null;

    /**
     * @var string|null $apiKey
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $apiKey = null;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->showSeasonScores = new ArrayCollection();
        $this->electionVotes = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->getDiscordId() ?: '';
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return '';
    }

    /**
     * @return string|null
     */
    public function getOauth2state(): ?string
    {
        return $this->oauth2state;
    }

    /**
     * @param string|null $oauth2state
     * @return $this
     */
    public function setOauth2state(?string $oauth2state): self
    {
        $this->oauth2state = $oauth2state;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordDiscriminator(): ?string
    {
        return $this->discordDiscriminator;
    }

    /**
     * @param string|null $discordDiscriminator
     * @return $this
     */
    public function setDiscordDiscriminator(?string $discordDiscriminator): self
    {
        $this->discordDiscriminator = $discordDiscriminator;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordId(): ?string
    {
        return $this->discordId;
    }

    /**
     * @param string|null $discordId
     * @return $this
     */
    public function setDiscordId(?string $discordId): self
    {
        $this->discordId = $discordId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordAvatar(): ?string
    {
        return $this->discordAvatar;
    }

    /**
     * @param string|null $discordAvatar
     * @return $this
     */
    public function setDiscordAvatar(?string $discordAvatar): self
    {
        $this->discordAvatar = $discordAvatar;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordLocal(): ?string
    {
        return $this->discordLocal;
    }

    /**
     * @param string|null $discordLocal
     * @return $this
     */
    public function setDiscordLocal(?string $discordLocal): self
    {
        $this->discordLocal = $discordLocal;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordToken(): ?string
    {
        return $this->discordToken;
    }

    /**
     * @param string|null $discordToken
     * @return $this
     */
    public function setDiscordToken(?string $discordToken): self
    {
        $this->discordToken = $discordToken;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordRefreshToken(): ?string
    {
        return $this->discordRefreshToken;
    }

    /**
     * @param string|null $discordRefreshToken
     * @return $this
     */
    public function setDiscordRefreshToken(?string $discordRefreshToken): self
    {
        $this->discordRefreshToken = $discordRefreshToken;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDiscordTokenExpires(): ?int
    {
        return $this->discordTokenExpires;
    }

    /**
     * @param int|null $discordTokenExpires
     * @return $this
     */
    public function setDiscordTokenExpires(?int $discordTokenExpires): self
    {
        $this->discordTokenExpires = $discordTokenExpires;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordUsername(): ?string
    {
        return $this->discordUsername;
    }

    /**
     * @param string|null $discordUsername
     */
    public function setDiscordUsername(?string $discordUsername): void
    {
        $this->discordUsername = $discordUsername;
    }

    /**
     * @return Collection|ShowSeasonScore[]
     */
    public function getShowSeasonScores(): Collection
    {
        return $this->showSeasonScores;
    }

    /**
     * @param ShowSeasonScore $showSeasonScore
     * @return $this
     */
    public function addShowSeasonScore(ShowSeasonScore $showSeasonScore): self
    {
        if (!$this->showSeasonScores->contains($showSeasonScore)) {
            $this->showSeasonScores[] = $showSeasonScore;
            $showSeasonScore->setUser($this);
        }

        return $this;
    }

    /**
     * @param ShowSeasonScore $showSeasonScore
     * @return $this
     */
    public function removeShowSeasonScore(ShowSeasonScore $showSeasonScore): self
    {
        // set the owning side to null (unless already changed)
        if ($this->showSeasonScores->removeElement($showSeasonScore) && $showSeasonScore->getUser() === $this) {
            $showSeasonScore->setUser(null);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUsername();
    }

    /**
     * @return Collection|ElectionVote[]
     */
    public function getElectionVotes(): Collection
    {
        return $this->electionVotes;
    }

    /**
     * @param ElectionVote $electionVote
     * @return $this
     */
    public function addElectionVote(ElectionVote $electionVote): self
    {
        if (!$this->electionVotes->contains($electionVote)) {
            $this->electionVotes[] = $electionVote;
            $electionVote->setUser($this);
        }

        return $this;
    }

    /**
     * @param ElectionVote $electionVote
     * @return $this
     */
    public function removeElectionVote(ElectionVote $electionVote): self
    {
        if ($this->electionVotes->removeElement($electionVote)) {
            // set the owning side to null (unless already changed)
            /** @noinspection NestedPositiveIfStatementsInspection */
            if ($electionVote->getUser() === $this) {
                $electionVote->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDisplayName(): ?string
    {
        return (empty($this->displayName)) ? $this->discordUsername : $this->displayName;
    }

    /**
     * @param string|null $displayName
     */
    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getPreferences(): UserPreferences
    {
        $preferences = new UserPreferences();
        $prefsValues = $this->prefsStore;
        $preferences->setColorsMode($prefsValues['colorsMode'] ?? 'os');
        $preferences->setAllWatchesViewMode($prefsValues['allWatchesViewMode'] ?? 'expanded');
        return $preferences;
    }

    public function setPreferences(UserPreferences $prefs): void
    {
        $this->prefsStore = $prefs->toArray();
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'displayName' => $this->getDisplayName(),
        ];
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * @param string|null $apiKey
     */
    public function setApiKey(?string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateApiKey(): void
    {
        if (null === $this->apiKey) {
            try {
                $this->apiKey = sha1(random_bytes(20));
            } catch (Exception $e) {
                $this->apiKey = null;
            }
        }
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement @method string getUserIdentifier()
    }
}
