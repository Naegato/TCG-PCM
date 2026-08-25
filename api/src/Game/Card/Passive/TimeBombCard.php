<?php

declare(strict_types=1);

namespace App\Game\Card\Passive;

use App\Enum\CardRarityEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Game\State\GameEvent;

final class TimeBombCard extends AbstractPassiveCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    private const TURNS_BEFORE_EXPLOSION = 3;
    private const DAMAGE = 15;

    private int $turnsActive = 0;

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::UNCOMMON;
    }

    public static function getGroups(): array
    {
        return [
            'bomb',
        ];
    }

    public function getId(): string
    {
        return 'TimeBomb';
    }

    public function getImage(): string
    {
        return 'timebomb.svg';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->getValue(self::DAMAGE, true),
            'value2' => $this->getValue(self::TURNS_BEFORE_EXPLOSION, true),
        ]);
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

        if ($this->turnsActive >= $this->getValue(self::TURNS_BEFORE_EXPLOSION, true)) {
            $gameContext->attack($this->getValue(self::DAMAGE, true));
            $gameContext->discardCard($instanceId);

            return;
        }

        $gameContext->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
            'cardId' => $instanceId,
            'stateToUpdate' => [
                'turnsActive' => ++$this->turnsActive,
            ],
        ]);
    }

    public function setState(CardState $state): void
    {
        parent::setState($state);

        $this->turnsActive = (int) ($state->values['turnsActive'] ?? 0);
    }
}
