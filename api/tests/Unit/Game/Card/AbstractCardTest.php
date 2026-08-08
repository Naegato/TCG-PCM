<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card;

use App\Enum\CardEffectEnum;
use App\Enum\CardTypeEnum;
use App\Game\AbstractCard;
use App\Game\Card\CardState;
use App\Game\Card\Effect\EffectState;
use App\Game\Card\Effect\TornedCardEffect;
use App\Game\Card\EffectCollection;
use PHPUnit\Framework\TestCase;

final class AbstractCardTest extends TestCase
{
    public function testSetState(): void
    {
        $state = new CardState('id', DummyCard::class, 'ownerId', []);

        $card = new DummyCard();
        $card->setState($state);

        self::assertSame('id', $card->getInstanceId());
        self::assertSame('ownerId', $card->getOwnerId());
    }

    public function testSetStateWithWrongId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $state = new CardState('id', 'badId', '', []);

        $card = new DummyCard();
        $card->setState($state);
    }

    public function testSetStateEffects(): void
    {
        $state = new CardState('id', DummyCard::class, 'ownerId', [
            new EffectState(CardEffectEnum::HACKED, [
                'value' => 200,
            ]),
        ]);

        $card = new DummyCard();
        $card->setState($state);

        self::assertCount(1, $card->getEffects()->all());
    }

    public function testSameEffectTwice()
    {
        $card = new DummyCard();

        $effect = TornedCardEffect::fromEffectState(new EffectState(CardEffectEnum::TORNED));

        $card->addEffect($effect);
        $card->addEffect(clone $effect);

        self::assertSame(1, count($card->getEffects()->all()));
    }
}

class DummyCard extends AbstractCard
{
    public function getId(): string
    {
        return self::class;
    }

    public function getName(): string
    {
        return 'Dummy Card';
    }

    public function getDescription(): string
    {
        return 'This is a dummy card for testing purposes.';
    }

    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::MONSTER;
    }

    public function getEffects(): EffectCollection
    {
        return $this->effects;
    }
}
