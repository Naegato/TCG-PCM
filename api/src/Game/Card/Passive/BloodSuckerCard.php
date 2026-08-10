<?php

namespace App\Game\Card\Passive;

use App\Enum\CardSetEnum;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Game\State\GameEvent;

final class BloodSuckerCard extends AbstractPassiveCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    public static CardSetEnum $serie = CardSetEnum::TBOI;

    public const int DAMAGE = 10;

    public function getId(): string
    {
        return 'BloodSucker';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE, true),
        ]);
    }

    public function onCardPlace(GameContext $gameContext): void
    {
        $this->suck($gameContext);
    }

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        $this->suck($gameContext);
    }

    private function suck(GameContext $gameContext): void
    {
        if (!$gameContext->isCurrentPlayer($this->getOwnerId())) {
            return;
        }

        $player2 = $gameContext->getOpponentState();

        $gameContext->attack($this->getValue(self::DAMAGE, true), $this->getOwnerId());
        $gameContext->attack($this->getValue(self::DAMAGE, true), $player2->player->id);
    }
}
