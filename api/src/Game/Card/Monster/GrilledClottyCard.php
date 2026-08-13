<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\State\GameEvent;

final class GrilledClottyCard extends AbstractMonsterCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::UNCOMMON;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::TBOI;
    }

    private const HEALTH_POINTS = 7;
    private const ATTACK = 21;
    private const SELF_DAMAGE = 1;

    public function getId(): string
    {
        return 'GrilledClotty';
    }

    public function getBaseAttack(): int
    {
        return self::ATTACK;
    }

    public function getHealPoints(): int
    {
        return self::HEALTH_POINTS;
    }

    public static function getGroups(): array
    {
        return ['clotty'];
    }

    public function onTurnEnd(GameEvent $event, GameContext $gameContext): void
    {
        $instanceId = $this->getInstanceId();
        if (null === $instanceId) {
            return;
        }

        $gameContext->damageCard($instanceId, self::SELF_DAMAGE);
    }
}
