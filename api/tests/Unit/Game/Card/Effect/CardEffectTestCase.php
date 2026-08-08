<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game\Card\Effect;

use App\Enum\CardTypeEnum;
use App\Game\AbstractCard;
use App\Game\Card\Effect\AbstractCardEffect;
use PHPUnit\Framework\TestCase;

abstract class CardEffectTestCase extends TestCase
{
    abstract protected function getEffect(): AbstractCardEffect;

    protected function getCardWithEffect(): TestCard
    {
        $card = new TestCard();
        $card->addEffect($this->getEffect());

        return $card;
    }
}

class TestCard extends AbstractCard
{
    public function getId(): string
    {
        return self::class;
    }

    public function getName(): string
    {
        return 'test';
    }

    public function getDescription(): string
    {
        return 'test';
    }

    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::MONSTER;
    }
}
