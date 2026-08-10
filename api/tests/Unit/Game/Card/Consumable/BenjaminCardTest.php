<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Consumable;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Consumable\BenjaminCard;
use App\Game\State\GameEvent;
use App\Tests\Unit\Game\Card\CardTestCase;

final class BenjaminCardTest extends CardTestCase
{
    public function testBenjaminCard(): void
    {
        $card = $this->getCard();
        $this->ensureNextDiceRolls(233);
        $ctx = $this->createGameContext();

        $card->play($ctx, ['target' => 'test_card']);

        $events = $ctx->flushEvents();

        self::assertCount(1, $events);
        self::assertEquals(new GameEvent(0, GameEventTypeEnum::EFFECT_ADDED, GameEvent::GAME_EVENT, [
            'effect' => 'Hacked',
            'cardId' => 'test_card',
            'effectValues' => ['value' => 233],
        ]), $events[0]);
    }

    protected function getCardFQCN(): string
    {
        return BenjaminCard::class;
    }
}
