<?php

declare(strict_types=1);

namespace App\Game;

abstract class Dice
{
    private static ?\Closure $generator = null;

    public static function setGenerator(?\Closure $generator): void
    {
        self::$generator = $generator;
    }

    public function d2(): int
    {
        return self::roll(2);
    }

    public static function d6(): int
    {
        return self::roll(6);
    }

    public static function d4(): int
    {
        return self::roll(4);
    }

    public static function d20(): int
    {
        return self::roll(20);
    }

    public static function d100(): int
    {
        return self::roll(100);
    }

    private static function roll(int $sides): int
    {
        if (self::$generator === null) {
            self::$generator = function (int $sides): int {
                return random_int(1, $sides);
            };
        }

        return (int) (self::$generator)($sides);
    }
}
