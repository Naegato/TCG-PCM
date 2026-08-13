<?php

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Game\Card\Interface\DeathAwareInterface;
use App\Game\Card\Trait\DeathAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class ImStillStandingCard extends AbstractPassiveCard implements DeathAwareInterface
{
    private const int REVIVE_HP_PERCENTAGE = 10;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::EPIC;
    }

    use DeathAwareTrait;

    public function getId(): string
    {
        return 'ImStillStanding';
    }

    public function getImage(): string
    {
        return 'https://i.discogs.com/K9fyMiGghoM2oaXJg4qTouYJ0rgiN3YZtfDcUDY6x0U/rs:fit/g:sm/q:90/h:600/w:600/czM6Ly9kaXNjb2dz/LWRhdGFiYXNlLWlt/YWdlcy9SLTU5MDc2/MC0xNTczNzk4MDIx/LTgyNTQuanBlZw.jpeg';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::REVIVE_HP_PERCENTAGE, true),
        ]);
    }

    public function onPlayerDeath(GameContext $gameContext, string $deadPlayerId): void
    {
        if ($deadPlayerId !== $this->getOwnerId()) {
            return;
        }

        $player = $gameContext->state->getPlayer($this->getOwnerId());

        $gameContext->heal((int) round(($player->maxHealthPoints * $this->getValue(self::REVIVE_HP_PERCENTAGE)) / 100), $this->getOwnerId());
        $gameContext->discardCard($this->getInstanceId());
    }
}
