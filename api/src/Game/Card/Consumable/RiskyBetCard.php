<?php

namespace App\Game\Card\Consumable;

use App\Game\GameContext;
use App\Game\GameUtils;

final class RiskyBetCard extends AbstractConsumableCard
{
    private const int OTHER_DAMAGE = 100;
    private const int SELF_DAMAGE = 50;

    public function getId(): string
    {
        return 'RiskyBet';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::OTHER_DAMAGE, true),
            'value2' => $this->getValue(self::SELF_DAMAGE, true),
        ]);
    }

    public function play(GameContext $context, array $data = []): void
    {
        $result = $context->rollDice(10);

        // 9 OR 10 deals damage
        if ($result >= 9) {
            $context->attack($this->getValue(self::OTHER_DAMAGE, true));
        } else {
            $context->attack($this->getValue(self::SELF_DAMAGE, true), $this->getOwnerId());
        }
    }
}
