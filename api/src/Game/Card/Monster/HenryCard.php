<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\State\GameEvent;

final class HenryCard extends AbstractMonsterCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::LEGENDARY;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::TBOI;
    }

    private const HEALTH_POINTS = 500;
    private const ATTACK = 0;

    public function getId(): string
    {
        return 'Henry';
    }

    public function getBaseAttack(): int
    {
        return self::ATTACK;
    }

    public function getHealPoints(): int
    {
        return self::HEALTH_POINTS;
    }

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->isOwnerTurn($event)) {
            return;
        }

        $instanceId = $this->getInstanceId();
        if (null === $instanceId) {
            return;
        }

        $gameContext->discardCard($instanceId);
    }
}
