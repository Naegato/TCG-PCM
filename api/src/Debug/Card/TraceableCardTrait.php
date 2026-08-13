<?php

declare(strict_types=1);

namespace App\Debug\Card;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Game\AbstractCard;
use App\Game\Card\CardState;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\Card\EffectCollection;
use App\Game\Card\Interface\CardAwareInterface;
use App\Game\Card\Interface\ComputedCardInterface;
use App\Game\Card\Interface\DeathAwareInterface;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Passive\AbstractPassiveCard;
use App\Game\GameContext;
use App\Game\State\GameEvent;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @template T of AbstractCard
 */
trait TraceableCardTrait
{
    public const STOPWATCH_CATEGORY = 'app.card';

    /** @var T $card */
    private AbstractCard $card;
    private Stopwatch $stopwatch;

    /**
     * @var string[] $methodCalled
     */
    private array $methodCalled = [];

    public function getId(): string
    {
        return $this->card->getId();
    }

    public function getInstanceId(): ?string
    {
        return $this->card->getInstanceId();
    }

    public function getName(): string
    {
        return $this->card->getName();
    }

    public function getDescription(): string
    {
        return $this->card->getDescription();
    }

    public function getImage(): string
    {
        return $this->card->getImage();
    }

    public function setState(CardState $state): void
    {
        $this->card->setState($state);
    }

    public function getEffects(): EffectCollection
    {
        return $this->card->getEffects();
    }

    /**
     * @return string[]
     */
    public function getMethodCalled(): array
    {
        return $this->methodCalled;
    }

    public function computeValue(): mixed
    {
        if ($this->card instanceof ComputedCardInterface) {
            return $this->card->computeValue();
        }

        return null;
    }

    public function setComputedValue(mixed $value): void
    {
        if ($this->card instanceof ComputedCardInterface) {
            $this->card->setComputedValue($value);
        }
    }

    public function onCardPlace(GameContext $gameContext): void
    {
        if (!$this->card instanceof AbstractPassiveCard) {
            return;
        }

        $this->methodCalled[] = __FUNCTION__;
        $this->stopwatch->start($id = $this->getEventName(__FUNCTION__), self::STOPWATCH_CATEGORY);

        $this->card->onCardPlace($gameContext);

        $this->stopwatch->stop($id);
    }

    public function onTurnStart(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->card instanceof TurnAwareInterface) {
            return;
        }

        $this->methodCalled[] = __FUNCTION__;
        $this->stopwatch->start($id = $this->getEventName(__FUNCTION__), self::STOPWATCH_CATEGORY);

        $this->card->onTurnStart($event, $gameContext);

        $this->stopwatch->stop($id);
    }

    public function onTurnEnd(GameEvent $event, GameContext $gameContext): void
    {
        if (!$this->card instanceof TurnAwareInterface) {
            return;
        }

        $this->methodCalled[] = __FUNCTION__;
        $this->stopwatch->start($id = $this->getEventName(__FUNCTION__), self::STOPWATCH_CATEGORY);

        $this->card->onTurnEnd($event, $gameContext);

        $this->stopwatch->stop($id);
    }

    public function onCardPlayed(AbstractCard $card, GameContext $gameContext): void
    {
        if (!$this->card instanceof CardAwareInterface) {
            return;
        }

        $this->methodCalled[] = __FUNCTION__;
        $this->stopwatch->start($id = $this->getEventName(__FUNCTION__), self::STOPWATCH_CATEGORY);

        $this->card->onCardPlayed($card, $gameContext);

        $this->stopwatch->stop($id);
    }

    public function onCardDrawn(string $cardId, GameContext $gameContext): void
    {
        if (!$this->card instanceof CardAwareInterface) {
            return;
        }

        $this->methodCalled[] = __FUNCTION__;
        $this->stopwatch->start($id = $this->getEventName(__FUNCTION__), self::STOPWATCH_CATEGORY);

        $this->card->onCardDrawn($cardId, $gameContext);

        $this->stopwatch->stop($id);
    }

    public function onCardDeath(AbstractCard $card, GameContext $gameContext): void
    {
        if (!$this->card instanceof DeathAwareInterface) {
            return;
        }

        $this->methodCalled[] = __FUNCTION__;
        $this->stopwatch->start($id = $this->getEventName(__FUNCTION__), self::STOPWATCH_CATEGORY);

        $this->card->onCardDeath($card, $gameContext);

        $this->stopwatch->stop($id);
    }

    public function onPlayerDeath(GameContext $gameContext, string $deadPlayerId): void
    {
        if (!$this->card instanceof DeathAwareInterface) {
            return;
        }

        $this->methodCalled[] = __FUNCTION__;
        $this->stopwatch->start($id = $this->getEventName(__FUNCTION__), self::STOPWATCH_CATEGORY);

        $this->card->onPlayerDeath($gameContext, $deadPlayerId);

        $this->stopwatch->stop($id);
    }

    public function getCost(): int
    {
        return $this->card->getCost();
    }

    public function requiresTarget(): bool
    {
        return $this->card instanceof AbstractConsumableCard && $this->card->requiresTarget();
    }

    public function getTargetType(): int
    {
        return $this->card instanceof AbstractConsumableCard ? $this->card->getTargetType() : AbstractConsumableCard::TARGET_TYPE_NONE;
    }

    public function getSerie(): CardSetEnum
    {
        return $this->card->getSerie();
    }

    public function getRarity(): CardRarityEnum
    {
        return $this->card->getRarity();
    }

    public function __clone()
    {
        $this->card = clone $this->card;
    }

    private function getEventName(string $method): string
    {
        return \sprintf('%s.%s (%d)', $this->getId(), $method, count($this->methodCalled) + 1);
    }
}
