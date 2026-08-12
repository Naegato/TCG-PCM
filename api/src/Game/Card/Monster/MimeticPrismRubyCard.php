<?php

namespace App\Game\Card\Monster;

use App\Enum\CardRarityEnum;
use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Service\Game\Helper\CardHelper;

class MimeticPrismRubyCard extends AbstractMonsterCard
{
    public static CardRarityEnum $rarity = CardRarityEnum::RARE;

    protected const HEALTH_POINTS_MULTIPLIER = 0.5;
    protected const ATTACK_MULTIPLIER = 2;

    private string $copyTemplateId;
    private int $damage;
    private int $heal;

    public function getId(): string
    {
        return 'MimeticPrismRuby';
    }

    public function setState(CardState $state): void
    {
        if (\is_string($state->values['templateId'] ?? null)) {
            $this->copyTemplateId = $state->values['templateId'];
            $this->getMimedCard();
        }

        parent::setState($state);
    }

    public function getBaseAttack(): int
    {
        return $this->damage ?? 1;
    }

    public function getHealPoints(): int
    {
        return $this->heal ?? 1;
    }

    public function onMonsterPlayed(GameContext $context): void
    {
        $pool = $context->getPlayerStateById($this->ownerId)->playArea->monsterCards;

        if ([] === $pool) {
            return;
        }

        $copyId = $context->selectRandomCardIn(array_filter($pool, fn($card) => $card !== $this->getInstanceId()));
        $state = $context->state->getCardState($copyId);

        if (!$state) {
            throw new \LogicException(sprintf('Card state with id "%s" not found', $copyId));
        }

        $this->copyTemplateId = $state->templateId;
        $this->getMimedCard();

        $context->pushGameEvent(GameEventTypeEnum::CARD_STATE_UPDATED, [
            'cardId' => $this->getInstanceId(),
            'stateToUpdate' => [
                'templateId' => $this->copyTemplateId,
            ],
            'currentHealthPoints' => $this->heal,
        ]);
    }

    protected function getMimedCard(): void
    {
        $cards = GameUtils::getService('cards');
        if (!$cards instanceof CardHelper) {
            throw new \LogicException('Service "cards" must be an instance of CardHelper.');
        }

        $card = $cards->getCardTemplate($this->copyTemplateId);
        if (!$card instanceof AbstractMonsterCard) {
            throw new \LogicException('Mimetic Prism Ruby can only mimic monster cards.');
        }

        $this->damage = (int) round($card->getBaseAttack() * static::ATTACK_MULTIPLIER);
        $this->heal = $this->currentHealthPoints = (int) round($card->getHealPoints() * static::HEALTH_POINTS_MULTIPLIER);
    }
}
