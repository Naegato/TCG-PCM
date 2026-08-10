<?php

declare(strict_types=1);

namespace App\Tests\Unit\Fixtures;

use App\Game\AbstractCard;
use App\Game\Card\Passive\AbstractPassiveCard;
use App\Game\Card\Interface\CardAwareInterface;
use App\Game\Card\Interface\DeathAwareInterface;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\GameContext;
use App\Game\State\GameEvent;

final class SpyAwareCard extends AbstractPassiveCard implements CardAwareInterface, TurnAwareInterface, DeathAwareInterface
{
    public static $calls = [];

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

    public function onCardDeath(AbstractCard $cardId, GameContext $gameContext): void
    {
        self::$calls[] = __METHOD__;
    }

    public function onPlayerDeath(GameContext $gameContext, string $deadPlayerId): void
    {
        self::$calls[] = __METHOD__;
    }

    public function onCardPlayed(AbstractCard $card, GameContext $gameContext): void
    {
        self::$calls[] = __METHOD__;
    }

    public function onCardDrawn(string $cardId, GameContext $gameContext): void
    {
        self::$calls[] = __METHOD__;
    }

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        self::$calls[] = __METHOD__;
    }

    public function onTurnEnd(GameEvent $event, GameContext $gameContext): void
    {
        self::$calls[] = __METHOD__;
    }

    public static function reset(): void
    {
        self::$calls = [];
    }
}
