<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Passive;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\Passive\PlacentaCard;
use App\Game\GameContext;
use App\Game\State\GameEvent;
use App\Tests\Unit\Game\Card\CardTestCase;

final class PlacentaCardTest extends CardTestCase
{
    public function getCardFQCN(): string
    {
        return PlacentaCard::class;
    }

    public function testTurnStart(): void
    {
        $card = $this->getCard();
        $gameContext = $this->createGameContext();
        $player = $gameContext->getCurrentPlayerState();
        $player = $player->withUpdatedHealth(10);
        $gameContext = new GameContext($gameContext->state->withUpdatedPlayer($player), $gameContext->playerId);

        $card->onTurnStart(new GameEvent(0, GameEventTypeEnum::TURN_STARTED, GameEvent::PLAYER_EVENT, ['playerId' => $card->getOwnerId()]), $gameContext);
        $events = $gameContext->flushEvents();

        self::assertCount(1, $events);
        self::assertSame(GameEventTypeEnum::HEAL, $events[0]->type);
    }
}
