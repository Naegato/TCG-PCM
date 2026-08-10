<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Provider;

use ApiPlatform\Metadata\Get;
use App\Api\Provider\CardCollectionProvider;
use App\Entity\Inventory\CardInventory;
use App\Entity\Inventory\Inventory;
use App\Entity\User;
use App\Enum\CardSetEnum;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\GameContext;
use App\Service\Auth\CurrentUserProviderInterface;
use App\Service\Game\CardRegistryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class CardCollectionProviderTest extends TestCase
{
    public function testProvideMergesAliasCardsByResolvedId(): void
    {
        $sharedCard = new AliasCardStub();

        $registry = $this->createMock(CardRegistryInterface::class);
        $registry->method('getAllBy')->with([])->willReturn(['alias-a', 'alias-b']);
        $registry->method('getCardTemplateById')->willReturnCallback(static fn(string $cardId) => clone $sharedCard);

        $inventory = $this->createStub(Inventory::class);
        $inventory
            ->method('getCards')
            ->willReturn(new ArrayCollection([
                $this->createCardInventory('alias-a', 2),
                $this->createCardInventory('alias-b', 3),
            ]));

        $user = $this->createStub(User::class);
        $user->method('getInventory')->willReturn($inventory);

        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('getCurrentUser')->willReturn($user);

        $provider = new CardCollectionProvider($currentUserProvider, $registry);
        $collection = $provider->provide(new Get(uriTemplate: '/inventory/collection'));

        self::assertCount(1, $collection->entries);
        self::assertSame('shared-card', $collection->entries[0]['card']->instanceId);
        self::assertSame(5, $collection->entries[0]['quantity']);
    }

    private function createCardInventory(string $cardId, int $quantity): CardInventory
    {
        $cardInventory = new CardInventory($this->createStub(Inventory::class), $cardId);
        $cardInventory->setQuantity($quantity);

        return $cardInventory;
    }
}

final class AliasCardStub extends AbstractConsumableCard
{
    public static CardSetEnum $serie = CardSetEnum::ORIGINAL;

    public function getId(): string
    {
        return 'shared-card';
    }

    public function getName(): string
    {
        return 'Shared Card';
    }

    public function getDescription(): string
    {
        return 'Shared card used to test alias merging.';
    }

    public function play(GameContext $context, array $data = []): void
    {
        // no-op
    }
}
