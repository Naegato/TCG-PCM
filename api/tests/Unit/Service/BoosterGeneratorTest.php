<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Enum\CardRarityEnum;
use App\Enum\CardSetEnum;
use App\Enum\CardTypeEnum;
use App\Game\AbstractCard;
use App\Game\Card\Character\PierrotCard;
use App\Game\GameContext;
use App\Service\Booster\BoosterGenerator;
use App\Service\Booster\BoosterRegistry;
use App\Tests\Resources\MockCardRegistry;
use Override;
use PHPUnit\Framework\TestCase;

final class BoosterGeneratorTest extends TestCase
{
    public function testGenerateBooster(): void
    {
        $boosterGenerator = new TestableBoosterGenerator();

        $booster = $boosterGenerator->generateBooster();

        self::assertCount(5, $booster->getCards());
    }

    public function testRarity(): void
    {
        $BoosterGenerator = new class extends TestableBoosterGenerator {
            protected const RARITY_PROBABILITIES = [
                CardRarityEnum::LEGENDARY->value => 1,
                CardRarityEnum::COMMON->value => 0,
            ];
        };

        $booster = $BoosterGenerator->generateBooster();

        self::assertInstanceOf(LegendaryCardStub::class, $booster->getCards()[0]);
    }

    public function testSize(): void
    {
        $boosterGenerator = new TestableBoosterGenerator();
        $booster = $boosterGenerator->generateBooster('big');

        self::assertCount(7, $booster->getCards());
    }
}

class TestableBoosterGenerator extends BoosterGenerator
{
    protected const RARITY_PROBABILITIES = [
        CardRarityEnum::LEGENDARY->value => 0.5,
        CardRarityEnum::COMMON->value => 0.5,
    ];

    public function __construct()
    {
        parent::__construct(new MockCardRegistry($this->getCardsList()), new BoosterRegistry());
    }

    public function getCardsList(): array
    {
        return [
            CommonCardStub::class => CommonCardStub::class,
            CommonCardStub::class.'1' => CommonCardStub::class,
            CommonCardStub::class.'2' => CommonCardStub::class,
            CommonCardStub::class.'3' => CommonCardStub::class,
            CommonCardStub::class.'4' => CommonCardStub::class,
            CommonCardStub::class.'5' => CommonCardStub::class,
            LegendaryCardStub::class => LegendaryCardStub::class,
            LegendaryCardStub::class.'1' => LegendaryCardStub::class,
            LegendaryCardStub::class.'2' => LegendaryCardStub::class,
            LegendaryCardStub::class.'3' => LegendaryCardStub::class,
            LegendaryCardStub::class.'4' => LegendaryCardStub::class,
            LegendaryCardStub::class.'5' => LegendaryCardStub::class,
            LegendaryCardStub::class.'6' => LegendaryCardStub::class,
            PierrotCard::class => PierrotCard::class,
        ];
    }
}

class CommonCardStub extends AbstractCard
{
    public function getId(): string
    {
        return '';
    }

    public function getName(): string
    {
        return 'Common Card Stub';
    }

    public function getDescription(): string
    {
        return 'A stub for common card testing.';
    }

    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::MONSTER;
    }

    public function play(GameContext $context): void
    {
        // No-op for testing
    }

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::COMMON;
    }

    public function getSerie(): CardSetEnum
    {
        return CardSetEnum::BTD6;
    }
}

class LegendaryCardStub extends AbstractCard
{
    public function getId(): string
    {
        return '';
    }

    public function getName(): string
    {
        return 'Legendary Card Stub';
    }

    public function getDescription(): string
    {
        return 'A stub for legendary card testing.';
    }

    public function getType(): CardTypeEnum
    {
        return CardTypeEnum::MONSTER;
    }

    public function play(GameContext $context): void
    {
        // No-op for testing
    }

    public function getRarity(): CardRarityEnum
    {
        return CardRarityEnum::LEGENDARY;
    }
}
