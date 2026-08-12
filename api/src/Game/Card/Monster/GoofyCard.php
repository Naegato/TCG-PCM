<?php

namespace App\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Game\State\GameEvent;

final class GoofyCard extends AbstractMonsterCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    private const DEFAULT_MODIFIER = 10;

    private int $attack = 10;

    private int $heal = 10;

    public function getId(): string
    {
        return 'Goofy';
    }

    public function getImage(): string
    {
        return 'https://www.shutterstock.com/image-photo/milan-lombardy-italy-november-20-260nw-2393466175.jpg';
    }

    public function getBaseAttack(): int
    {
        return $this->attack;
    }

    public function getHealPoints(): int
    {
        return $this->heal;
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => -self::DEFAULT_MODIFIER,
            'value2' => '+'.self::DEFAULT_MODIFIER,
        ]);
    }

    public function setState(CardState $state): void
    {
        parent::setState($state);

        $this->attack = (int) ($state->values['attack'] ?? 10);
    }

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->isOwnerTurn($event)) {
            return;
        }

        $attackModifier = $gameContext->randomIntBetween(-self::DEFAULT_MODIFIER, self::DEFAULT_MODIFIER);
        $pvModifier = $gameContext->randomIntBetween(-self::DEFAULT_MODIFIER, self::DEFAULT_MODIFIER);

        $this->attack += $attackModifier;
        $this->currentHealthPoints += $pvModifier;

        if ($this->attack < 0) {
            $this->attack = 0;
        }

        $gameContext->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
            'cardId' => $this->getInstanceId(),
            'currentHealthPoints' => $this->currentHealthPoints,
            'stateToUpdate' => [
                'attack' => $this->attack,
            ],
        ]);
    }
}
