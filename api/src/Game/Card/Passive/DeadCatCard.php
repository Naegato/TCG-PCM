<?php

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Interface\DeathAwareInterface;
use App\Game\Card\Trait\DeathAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class DeadCatCard extends AbstractPassiveCard implements DeathAwareInterface
{
    use DeathAwareTrait;

    private const int REVIVE_HP = 1;

    private int $counter = 9;

    public function getId(): string
    {
        return 'DeadCat';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), ['value' => $this->getValue(self::REVIVE_HP), 'value2' => $this->counter]);
    }

    public function onPlayerDeath(GameContext $gameContext, string $deadPlayerId): void
    {
        if ($deadPlayerId !== $this->getOwnerId()) {
            return;
        }

        $player = $gameContext->getPlayerStateById($this->getOwnerId());

        $gameContext->heal(abs(min(0, $player->healthPoints)) + self::REVIVE_HP, $this->getOwnerId());

        $gameContext->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
            'cardId' => $this->getInstanceId(),
            'stateToUpdate' => [
                'counter' => --$this->counter,
            ],
        ]);

        if ($this->counter <= 0) {
            $gameContext->discardCard($this->getInstanceId());
        }
    }

    public function setState(CardState $state): void
    {
        parent::setState($state);

        $this->counter = $state->values['counter'] ?? 9;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::TBOI;
    }

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::RARE;
    }
}
