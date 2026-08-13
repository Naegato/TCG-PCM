<?php

declare(strict_types=1);

namespace App\Game\Card\Character;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\State\GameEvent;

final class IsaacCard extends AbstractCharacterCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::EPIC;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::TBOI;
    }

    private const DAMAGE = 5;

    public function getId(): string
    {
        return 'Isaac';
    }

    public function getHealthPoints(): int
    {
        return 180;
    }

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->isOwnerTurn($event)) {
            return;
        }

        if (null === ($ownerId = $this->getOwnerId())) {
            return;
        }

        $opponentState = $gameContext->getPlayerStateById($gameContext->getOtherPlayerId($ownerId));
        $targetPool = [...$opponentState->playArea->monsterCards, $opponentState->characterCardId];
        $targetId = $gameContext->selectRandomCardIn($targetPool);

        $gameContext->damageCard($targetId, self::DAMAGE);
    }
}
