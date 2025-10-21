<?php

declare(strict_types=1);

namespace App\Tests\Game\Card;

use App\Game\AbstractCard;
use App\Game\Dice;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

abstract class CardTestCase extends TestCase
{
    abstract protected function getCardFQCN(): string;

    #[Before]
    public function afterAll(): void
    {
        Dice::setGenerator(null);
    }

    public function getCard(): AbstractCard
    {
        return new ($this->getCardFQCN())();
    }

    protected function ensureNextDiceRolls(int $result): void
    {
        Dice::setGenerator(fn ($sides) => $result);
    }

    protected static function allRollFromGenerator(int $count): \Generator
    {
        for ($i = 1; $i <= $count; $i++) {
            yield 'Test roll: '.$i => [$i];
        }
    }
}
