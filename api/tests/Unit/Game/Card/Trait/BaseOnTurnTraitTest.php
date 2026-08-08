<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Trait;

use App\Enum\CardTypeEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\AbstractCard;
use App\Game\Card\CardState;
use App\Game\Card\Trait\BaseOnTurnTrait;
use App\Game\GameContext;
use App\Game\State\GameEvent;
use PHPUnit\Framework\TestCase;

final class BaseOnTurnTraitTest extends TestCase
{
    public function testNoAction()
    {
        $card = new TestCard();
        $gameContext = $this->createStub(GameContext::class);
        $gameContext->method('isCurrentPlayer')->willReturn(true);
        $card->setState(new CardState('', TestCard::class, '', [], []));

        $card->onTurnStart(new GameEvent(0, GameEventTypeEnum::TURN_STARTED, GameEvent::PLAYER_EVENT, ['playerId' => '2']), $gameContext);

        self::assertFalse($card::$actionExecuted);
    }

    public function testAction()
    {
        $card = new TestCard();
        $gameContext = $this->createStub(GameContext::class);
        $gameContext->method('isCurrentPlayer')->willReturn(true);
        $card->setState(
            new CardState(
                '',
                TestCard::class,
                '',
                [],
                [
                    'turnRemainingBeforeAction' => 1,
                ],
            ),
        );

        $card->onTurnStart(new GameEvent(0, GameEventTypeEnum::TURN_STARTED, GameEvent::PLAYER_EVENT, ['playerId' => '1']), $gameContext);

        self::assertTrue($card::$actionExecuted);
    }
}

class TestCard extends AbstractCard
{
    use BaseOnTurnTrait;

    public static bool $actionExecuted = false;

    public function getId(): string
    {
        return self::class;
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getName(): string
    {
        return '';
    }

    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::MONSTER;
    }

    public function getTurnDelay(): int
    {
        return 5;
    }

    public function getInstanceId(): ?string
    {
        return (string) spl_object_id($this);
    }

    public function onTurnAction(GameContext $gameContext): void
    {
        self::$actionExecuted = true;
    }

    public function setState(CardState $state): void
    {
        parent::setState($state);

        $this->initFromState($state);
    }

    public function getOwnerId(): ?string
    {
        return '1';
    }
}
