<?php

declare(strict_types=1);

namespace App\Enum;

enum CardTriggerEnum: string
{
    case TURN_START = 'TURN_START';
    case TURN_END = 'TURN_END';
    case CARD_PLAYED = 'CARD_PLAYED';
    case CARD_DRAWN = 'CARD_DRAWN';
    case CARD_DEATH = 'CARD_DEATH';
    case PLAYER_DEATH = 'PLAYER_DEATH';
}
