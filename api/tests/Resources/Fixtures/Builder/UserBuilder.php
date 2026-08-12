<?php

declare(strict_types=1);

namespace App\Tests\Resources\Fixtures\Builder;

use App\Entity\Inventory\Inventory;
use App\Entity\User;
use App\Tests\Resources\Fixtures\ThereIs;

/**
 * @extends AbstractBuilder<User>
 */
final class UserBuilder extends AbstractBuilder
{
    protected static array $usedIds = [];

    private Inventory $inventory;
    private int $boosterTokens = 1;
    private \DateTimeImmutable $lastBoosterTokensAt;
    private array $roles = [];

    public function build(): object
    {
        $user = parent::build();
        $this->lastBoosterTokensAt ??= new \DateTimeImmutable();

        $this->inventory ??= ThereIs::anInventory()->for($user)->build();
        $user->setInventory($this->inventory);
        $userWallet = ThereIs::aUserWallet()->for($user)->withBoosterTokens($this->boosterTokens)->build();
        $user->setUserWallet($userWallet);
        $userInfo = ThereIs::aUserInfo()->for($user)->withLastBoosterTokensAt($this->lastBoosterTokensAt)->build();
        $user->setUserInfo($userInfo);
        return $user;
    }

    public function withInventory(Inventory $inventory): self
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function withBoosterTokens(int $boosterTokens): self
    {
        $this->boosterTokens = $boosterTokens;

        return $this;
    }

    public function withLastBoosterTokensAt(\DateTimeImmutable $lastBoosterTokensAt): self
    {
        $this->lastBoosterTokensAt = $lastBoosterTokensAt;

        return $this;
    }

    public function withRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function asAdmin(): self
    {
        return $this->withRoles(['ROLE_ADMIN']);
    }

    protected function doBuild(): void
    {
        $id = 'user_'.spl_object_id($this);

        if (\in_array($id, self::$usedIds, true)) {
            $id = 'user_'.spl_object_id($this).'-'.count(self::$usedIds);
        }
        self::$usedIds[] = $id;

        $this->entity = new User($id, $id.'@test.local');
        $this->entity->setPassword('password');
        $this->entity->setRoles($this->roles);
    }
}
