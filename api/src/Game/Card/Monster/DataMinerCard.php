<?php

namespace App\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Game\State\GameEvent;
use Override;

final class DataMinerCard extends AbstractMonsterCard implements TurnAwareInterface
{
    use TurnAwareTrait;

    private const HEALTH_POINTS = 10;
    private const ATTACK = 5;

    private int $currentCoins = 0;

    public function getId(): string
    {
        return 'DataMiner';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => $this->currentCoins,
            'const' => 1,
        ]);
    }

    public function getImage(): string
    {
        return 'https://thumbs.dreamstime.com/z/server-computer-component-database-big-data-storage-cartoon-eyes-mascot-cute-funny-smile-tech-object-vector-drawing-66747778.jpg';
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

        $gameContext->addCoins($this->getValue($this->currentCoins, true), $this->getOwnerId());
    }

    public function onTurnEnd(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->isOwnerTurn($event)) {
            return;
        }

        $gameContext->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
            'cardId' => $this->getInstanceId(),
            'stateToUpdate' => [
                'currentCoins' => ++$this->currentCoins,
            ],
        ]);
    }

    public function setState(CardState $state): void
    {
        parent::setState($state);

        $this->currentCoins = (int) ($state->values['currentCoins'] ?? 0);
    }
}
