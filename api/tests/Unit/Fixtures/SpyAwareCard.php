<?php

declare(strict_types=1);

namespace App\Tests\Unit\Fixtures;

use App\Game\AbstractCard;
use App\Game\Card\Interface\CardAwareInterface;
use App\Game\Card\Interface\DeathAwareInterface;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Passive\AbstractPassiveCard;
use App\Game\GameContext;
use App\Game\State\GameEvent;

final class SpyAwareCard extends AbstractPassiveCard implements CardAwareInterface, TurnAwareInterface, DeathAwareInterface
{
    public static $calls = [];

    /**
     * One entry per call, capturing the scalar/object arguments actually received — lets tests
     * assert that CARD_TRIGGERED_ACTION correctly threads data through (e.g. the right event, the
     * right drawn card id) rather than just counting how many times a hook fired.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $receivedCalls = [];

    public function getId(): string
    {
        return self::class;
    }

    public function getName(): string
    {
        return 'Spy';
    }

    public function getDescription(): string
    {
        return $this->getName();
    }

    public function onCardDeath(AbstractCard $card, GameContext $gameContext): void
    {
        self::$calls[] = __METHOD__;
        self::$receivedCalls[] = ['method' => 'onCardDeath', 'card' => $card];
    }

    public function onPlayerDeath(GameContext $gameContext, string $deadPlayerId): void
    {
        self::$calls[] = __METHOD__;
        self::$receivedCalls[] = ['method' => 'onPlayerDeath', 'deadPlayerId' => $deadPlayerId];
    }

    public function onCardPlayed(AbstractCard $card, GameContext $gameContext): void
    {
        self::$calls[] = __METHOD__;
        self::$receivedCalls[] = ['method' => 'onCardPlayed', 'card' => $card];
    }

    public function onCardDrawn(string $cardId, GameContext $gameContext): void
    {
        self::$calls[] = __METHOD__;
        self::$receivedCalls[] = ['method' => 'onCardDrawn', 'cardId' => $cardId];
    }

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        self::$calls[] = __METHOD__;
        self::$receivedCalls[] = ['method' => 'onTurnStart', 'event' => $event];
    }

    public function onTurnEnd(GameEvent $event, GameContext $gameContext): void
    {
        self::$calls[] = __METHOD__;
        self::$receivedCalls[] = ['method' => 'onTurnEnd', 'event' => $event];
    }

    public static function reset(): void
    {
        self::$calls = [];
        self::$receivedCalls = [];
    }
}
