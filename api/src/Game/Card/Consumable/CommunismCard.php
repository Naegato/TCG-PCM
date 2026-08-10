<?php

namespace App\Game\Card\Consumable;

use App\Game\GameContext;

final class CommunismCard extends AbstractConsumableCard
{
    public function getId(): string
    {
        return 'Communism';
    }

    public function play(GameContext $context, array $data = []): void
    {
        $player1Coins = $context->getCurrentPlayerState()->coins;
        $player2Coins = $context->getOpponentState()->coins;

        $totalCoins = $player1Coins + $player2Coins;

        $isOdd = ($totalCoins % 2) === 1;
        if ($isOdd) {
            $totalCoins -= 1;
        }

        $newCoins = (int) ($totalCoins / 2);

        $context->setCoins($newCoins + ($isOdd ? 1 : 0), $this->getOwnerId());
        $context->setCoins($newCoins, $context->getOpponent()->id);
    }
}
