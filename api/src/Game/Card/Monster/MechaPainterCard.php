<?php

declare(strict_types=1);

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Game\State\GameEvent;

final class MechaPainterCard extends AbstractMonsterCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::TBOI;
    }

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::LEGENDARY;
    }

    private const HEALTH_POINTS = 39;
    private const ATTACK = 45;
    private const DAMAGE_REDUCTION = 10;
    private const SELF_TEAM_DAMAGE = 10;

    public function getId(): string
    {
        return 'MechaPainter';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE_REDUCTION, true),
            'value2' => $this->getValue(self::SELF_TEAM_DAMAGE, true),
        ]);
    }

    public function getBaseAttack(): int
    {
        return self::ATTACK;
    }

    public function getHealPoints(): int
    {
        return self::HEALTH_POINTS;
    }

    public function reduceDamage(GameContext $context, int $damage): int
    {
        return max(0, $damage - self::DAMAGE_REDUCTION);
    }

    public function onTurnEnd(GameEvent $event, GameContext $gameContext): void
    {
        $ownerId = $this->getOwnerId();

        if (null === $ownerId) {
            return;
        }

        $ownerState = $gameContext->getPlayerStateById($ownerId);
        $pool = $ownerState->playArea->monsterCards;
        $pool = array_filter($pool, fn(string $cardId) => $cardId !== $this->getInstanceId());

        if ([] === $pool) {
            return;
        }

        $targetId = $gameContext->selectRandomCardIn($pool);

        $gameContext->pushGameEvent(GameEventTypeEnum::DAMAGE_DEALT, [
            'targetId' => $targetId,
            'damage' => $this->getValue(self::SELF_TEAM_DAMAGE, true),
        ]);
    }
}
