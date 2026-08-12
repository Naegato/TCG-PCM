<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Api\Processor\SetProfilePictureProcessor;
use App\Api\Provider\UserProvider;
use App\Api\Provider\UserSearchProvider;
use App\Domain\Command\User\ChangePasswordCommand;
use App\Domain\Command\User\GenerateBoosterTokensCommand;
use App\Domain\Command\User\RegisterCommand;
use App\Domain\Command\User\RequestPasswordResetCommand;
use App\Domain\Command\User\ResetPasswordCommand;
use App\Domain\Command\User\SetProfilePictureCommand;
use App\Entity\Inventory\Inventory;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(operations: [
    new Get(uriTemplate: '/user', provider: UserProvider::class, normalizationContext: ['groups' => 'api:user:read']),
    new GetCollection(uriTemplate: '/users/search', provider: UserSearchProvider::class, normalizationContext: ['groups' => 'api:friendship:read']),
    new Post(uriTemplate: '/user/generate_booster_tokens', messenger: 'input', input: GenerateBoosterTokensCommand::class, status: 200),
    new Post(
        uriTemplate: '/user/profile_picture',
        inputFormats: ['multipart' => ['multipart/form-data']],
        input: SetProfilePictureCommand::class,
        deserialize: false,
        processor: SetProfilePictureProcessor::class,
        status: 200,
    ),
    new Post(uriTemplate: '/register', condition: "is_enable('register')", messenger: 'input', input: RegisterCommand::class, status: 201),
    new Post(uriTemplate: '/forgot-password', messenger: 'input', input: RequestPasswordResetCommand::class, status: 200),
    new Post(uriTemplate: '/reset-password', messenger: 'input', input: ResetPasswordCommand::class, status: 200),
    new Post(uriTemplate: '/change-password', messenger: 'input', input: ChangePasswordCommand::class, status: 200),
])]
// @mago-ignore lint:too-many-properties
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $username;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column]
    private \DateTimeImmutable $passwordChangedAt;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $googleId = null;

    /**
     * @var Collection<int, UserBadge>
     */
    #[ORM\OneToMany(targetEntity: UserBadge::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userBadges;

    /**
     * @var Collection<int, Deck>
     */
    #[ORM\OneToMany(targetEntity: Deck::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $decks;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicturePath = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private UserWallet $userWallet;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private UserInfo $userInfo;

    #[ORM\OneToOne(mappedBy: 'owner', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Inventory $inventory;

    public function __construct(string $username, string $email = '')
    {
        $this->username = $username;
        $this->email = $email;
        $this->passwordChangedAt = new \DateTimeImmutable();
        $this->userBadges = new ArrayCollection();
        $this->decks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        // @mago-ignore analyse:invalid-return-statement
        return $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(#[\SensitiveParameter] string $password): static
    {
        $this->password = $password;
        $this->passwordChangedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPasswordChangedAt(): \DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = [
            'username' => $this->username,
        ];
        if ($this->password) {
            $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        }

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * @return Collection<int, UserBadge>
     */
    public function getUserBadges(): Collection
    {
        return $this->userBadges;
    }

    public function addUserBadge(UserBadge $userBadge): static
    {
        if (!$this->userBadges->contains($userBadge)) {
            $this->userBadges->add($userBadge);
            $userBadge->setUser($this);
        }

        return $this;
    }

    public function removeUserBadge(UserBadge $userBadge): static
    {
        if ($this->userBadges->removeElement($userBadge)) {
            // set the owning side to null (unless already changed)
            if ($userBadge->getUser() === $this) {
                $userBadge->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Deck>
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    public function addDeck(Deck $deck): static
    {
        if (!$this->decks->contains($deck)) {
            $this->decks->add($deck);
        }

        return $this;
    }

    public function removeDeck(Deck $deck): static
    {
        $this->decks->removeElement($deck);

        return $this;
    }

    public function getInventory(): Inventory
    {
        return $this->inventory;
    }

    public function setInventory(Inventory $inventory): static
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function getProfilePicturePath(): ?string
    {
        return $this->profilePicturePath;
    }

    public function setProfilePicturePath(?string $profilePicturePath): static
    {
        $this->profilePicturePath = $profilePicturePath;

        return $this;
    }

    public function getUserWallet(): UserWallet
    {
        return $this->userWallet;
    }

    public function setUserWallet(UserWallet $userWallet): static
    {
        $this->userWallet = $userWallet;

        return $this;
    }

    public function getUserInfo(): UserInfo
    {
        return $this->userInfo;
    }

    public function setUserInfo(UserInfo $userInfo): static
    {
        $this->userInfo = $userInfo;

        return $this;
    }

    #[ApiProperty]
    #[SerializedName('isAdmin')]
    public function isAdmin(): bool
    {
        return \in_array('ROLE_ADMIN', $this->getRoles(), true);
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }
}
