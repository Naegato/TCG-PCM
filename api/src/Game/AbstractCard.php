<?php

declare(strict_types=1);

namespace App\Game;

use App\Enum\CardEffectEnum;
use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\CardTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Effect\AbstractCardEffect;
use App\Game\Card\EffectCollection;

abstract class AbstractCard
{
    protected EffectCollection $effects;

    private string $instanceId;

    protected string $ownerId;

    // @final to prevent child classes from having constructors with different signatures
    final public function __construct()
    {
        $this->effects = new EffectCollection();
    }

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::COMMON;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::ORIGINAL;
    }

    public static function getGroups(): array
    {
        return [];
    }

    abstract public function getId(): string;

    abstract public function getType(): CardTypeEnum;

    public function getInstanceId(): ?string
    {
        return $this->instanceId ?? null;
    }

    public function getOwnerId(): ?string
    {
        return $this->ownerId ?? null;
    }

    public function getName(): string
    {
        return GameUtils::t(\sprintf('card.%s.name', $this->getId()));
    }

    public function getDescription(): string
    {
        return GameUtils::t(\sprintf('card.%s.description', $this->getId()));
    }

    public function getImage(): string
    {
        return \sprintf('%s.png', $this->getId());
    }

    /**
     * @param bool $forceInt
     * @return ($forceInt is true ? int : float)
     */
    public function getValue(float|int $value, bool $forceInt = false): float|int
    {
        foreach ($this->effects->all() as $effect) {
            $value = $effect->modifyValue($value, $this);
        }

        return $forceInt ? (int) round($value) : (float) $value;
    }

    public function beforeAction(GameContext $gameContext): void
    {
        foreach ($this->effects->all() as $effect) {
            $effect->beforeAction($this, $gameContext);
        }
    }

    public function addEffect(AbstractCardEffect $effect): void
    {
        if (!$this->effects->has($effect)) {
            $this->effects->add($effect);
        }
    }

    public function removeEffect(AbstractCardEffect $effect): void
    {
        $this->effects->remove($effect->getName());
    }

    public function getEffects(): EffectCollection
    {
        return $this->effects;
    }

    public function getEffect(CardEffectEnum $effectName): ?AbstractCardEffect
    {
        return $this->effects->get($effectName);
    }

    public function setState(CardState $state): void
    {
        if ($this->getId() !== $state->templateId) {
            throw new \InvalidArgumentException(\sprintf('Cannot add state for card %s to card %s', $state->templateId, $this->getId()));
        }

        $this->instanceId = $state->instanceId;
        $this->ownerId = $state->ownerId;
        foreach ($state->effects as $effectState) {
            $effect = $effectState->effect->getClass()::fromEffectState($effectState);

            if (!$effect instanceof AbstractCardEffect) {
                throw new \LogicException(\sprintf('Effect class %s does not implement AbstractCardEffect', $effectState->effect->getClass()));
            }

            $this->addEffect($effect);
        }
    }

    public function getCost(): int
    {
        return match ($this->getRarity()) {
            CardRarityEnum::COMMON => 1,
            CardRarityEnum::UNCOMMON => 2,
            CardRarityEnum::RARE => 3,
            CardRarityEnum::EPIC => 4,
            CardRarityEnum::LEGENDARY => 5,
        };
    }

    public function __clone()
    {
        $this->effects = clone $this->effects;
    }
}
