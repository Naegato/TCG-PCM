<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Game;

use App\Game\Card\Character\AbstractCharacterCard;
use App\Game\Card\Character\PierrotCard;
use App\Game\Card\Monster\DartMonkeyCard;
use App\Game\Card\Monster\MOABCard;
use App\Tests\Resources\MockCardRegistry;
use PHPUnit\Framework\TestCase;

final class CardRegistryTest extends TestCase
{
    public function testGetAllByWithExcludeTypeFiltersMatchingCards(): void
    {
        $registry = new MockCardRegistry([
            'character-card' => PierrotCard::class,
            'monster-card' => DartMonkeyCard::class,
        ]);

        $cards = $registry->getAllBy([
            'excludeType' => AbstractCharacterCard::class,
        ]);

        self::assertSame(['monster-card'], $cards);
    }

    public function testGetAllByWithExcludeTypeRejectsInvalidClass(): void
    {
        $registry = new MockCardRegistry([
            'monster-card' => DartMonkeyCard::class,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exclude type must be a class string of App\Game\AbstractCard');

        $registry->getAllBy([
            'excludeType' => \stdClass::class,
        ]);
    }

    public function testGetAllByWithGroupsFiltersCardsByIntersection(): void
    {
        $registry = new MockCardRegistry([
            'monkey-card' => DartMonkeyCard::class,
            'bloon-card' => MOABCard::class,
            'character-card' => PierrotCard::class,
        ]);

        $cards = $registry->getAllBy([
            'groups' => ['monkey'],
        ]);

        self::assertSame(['monkey-card'], $cards);
    }

    public function testHasReturnsTrueForExistingCard(): void
    {
        $registry = new MockCardRegistry([
            'monster-card' => DartMonkeyCard::class,
        ]);

        self::assertTrue($registry->has('monster-card'));
    }

    public function testHasReturnsFalseForUnknownCard(): void
    {
        $registry = new MockCardRegistry([
            'monster-card' => DartMonkeyCard::class,
        ]);

        self::assertFalse($registry->has('unknown-card'));
    }
}
