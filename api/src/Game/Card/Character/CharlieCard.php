<?php

namespace App\Game\Card\Character;

use App\Game\Card\CardActions;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Passive\AbstractPassiveCard;
use App\Game\Card\Trait\BaseOnTurnTrait;
use App\Game\GameContext;
use App\Game\GameUtils;

final class CharlieCard extends AbstractCharacterCard implements TurnAwareInterface
{
    use BaseOnTurnTrait;

    private const TURN_DELAY = 2;

    /**
     * @var string[]|null
     */
    private static ?array $passiveTemplatePool = null;

    public function getId(): string
    {
        return 'Charlie';
    }

    public function getHealthPoints(): int
    {
        return 125;
    }

    public function getTurnDelay(): int
    {
        return $this->getValue(self::TURN_DELAY, true);
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value1' => 1,
            'value2' => $this->getTurnDelay(),
        ]);
    }

    public function onTurnAction(GameContext $context): void
    {
        if ($context->isCurrentPlayer($this->getOwnerId())) {
            $randomPassiveCardId = $this->pickRandomPassiveCardId($context);

            CardActions::generatedAndPlay($context, $this->getOwnerId(), $randomPassiveCardId, false);
        }
    }

    private function pickRandomPassiveCardId(GameContext $context): string
    {
        $pool = $this->getPassiveTemplatePool();

        if ([] === $pool) {
            throw new \LogicException('Charlie template pool cannot be empty');
        }

        return $pool[$context->state->randomizer->randomBetweenInt(0, count($pool) - 1)];
    }

    /**
     * @return string[]
     */
    private function getPassiveTemplatePool(): array
    {
        return self::$passiveTemplatePool ??= GameUtils::getService('cards')->getCardsBy([
            'type' => AbstractPassiveCard::class,
        ]);
    }
}
