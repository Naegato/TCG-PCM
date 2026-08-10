<?php

declare(strict_types=1);

namespace App\Debug\Card;

use App\Game\AbstractCard;
use App\Game\Card\Passive\AbstractPassiveCard;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\Card\CardState;
use App\Game\Card\Character\AbstractCharacterCard;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Service\Game\CardFactoryInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class TraceableCardFactory implements CardFactoryInterface
{
    private const STOPWATCH_CATEGORY = 'app.card_factory';

    /**
     * @var AbstractCard[] $cards
     */
    private array $cards = [];

    public function __construct(
        private CardFactoryInterface $decoratedFactory,
        private Stopwatch $stopwatch,
    ) {}

    public function create(string $cardId): AbstractCard
    {
        $this->stopwatch->start($id = 'create_card.'.$cardId, self::STOPWATCH_CATEGORY);

        $card = $this->decoratedFactory->create($cardId);

        $this->stopwatch->stop($id);

        return $this->trackCard($card);
    }

    public function createWithState(string $cardId, CardState $state): AbstractCard
    {
        $this->stopwatch->start($id = 'create_card_with_state.'.$cardId, self::STOPWATCH_CATEGORY);

        $card = $this->decoratedFactory->createWithState($cardId, $state);

        $this->stopwatch->stop($id);

        return $this->trackCard($card);
    }

    public function getCards(): array
    {
        return $this->cards;
    }

    public function hasCards(): bool
    {
        return [] !== $this->cards;
    }

    private function trackCard(AbstractCard $card): AbstractCard
    {
        if ($card instanceof AbstractConsumableCard) {
            $card = TraceableConsumableCard::create($card, $this->stopwatch);
        } elseif ($card instanceof AbstractPassiveCard) {
            $card = TraceablePassiveCard::create($card, $this->stopwatch);
        } elseif ($card instanceof AbstractCharacterCard) {
            $card = TraceableCharacterCard::create($card, $this->stopwatch);
        } elseif ($card instanceof AbstractMonsterCard) {
            $card = TraceableMonsterCard::create($card, $this->stopwatch);
        }

        return $this->cards[] = $card;
    }
}
