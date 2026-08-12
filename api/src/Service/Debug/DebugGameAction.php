<?php

declare(strict_types=1);

namespace App\Service\Debug;

final readonly class DebugGameAction
{
    public const string GIVE_CARD = 'give_card';
    public const string SET_STATS = 'set_stats';
    public const string FORCE_END_TURN = 'force_end_turn';
    public const string FORCE_SET_CURRENT_PLAYER = 'force_set_current_player';
    public const string REMOVE_CARD = 'remove_card';

    public const array ACTIONS = [
        self::GIVE_CARD,
        self::SET_STATS,
        self::FORCE_END_TURN,
        self::FORCE_SET_CURRENT_PLAYER,
        self::REMOVE_CARD,
    ];

    public function __construct(
        public string $adminId,
        public string $actionId,
        public string $gameId,
        public array $payload,
    ) {}
}
