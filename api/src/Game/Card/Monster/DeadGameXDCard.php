<?php

namespace App\Game\Card\Monster;

use App\Enum\GameEventTypeEnum;
use App\Game\Card\CardState;
use App\Game\Card\Interface\ComputedCardInterface;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Service\Game\Helper\HttpHelper;

final class DeadGameXDCard extends AbstractMonsterCard implements ComputedCardInterface
{
    private const int GAME_ID = 444_090;

    private const ATTACK_ENDPOINT = 'https://store.steampowered.com/api/appdetails?appids=';
    private const HEALTH_POINTS_ENDPOINT = 'https://api.steampowered.com/ISteamUserStats/GetNumberOfCurrentPlayers/v1/?appid=';

    private int $healthPoints = 0;
    private int $attack = 0;

    public function getId(): string
    {
        return 'DeadGameXD';
    }

    public function getBaseAttack(): int
    {
        return $this->attack;
    }

    public function getHealPoints(): int
    {
        return $this->healthPoints;
    }

    public function setState(CardState $state): void
    {
        if (isset($state->values['attack'])) {
            $this->attack = (int) $state->values['attack'];
        }

        if (isset($state->values['healthPoints'])) {
            $this->healthPoints = (int) $state->values['healthPoints'];
        }

        parent::setState($state);
    }

    public function computeValue(): mixed
    {
        if ($this->attack > 0 && $this->healthPoints > 0) {
            return [
                'attack' => $this->attack,
                'healthPoints' => $this->healthPoints,
            ];
        }

        /** @var HttpHelper $client */
        $client = GameUtils::getService('http');

        $response = $client->get(self::ATTACK_ENDPOINT.self::GAME_ID);

        $data = $response->toArray();

        $this->attack = (int) round($data[self::GAME_ID]['data']['metacritic']['score'] / 10);

        $response = $client->get(self::HEALTH_POINTS_ENDPOINT.self::GAME_ID);

        $data = $response->toArray();

        $this->healthPoints = (int) round($data['response']['player_count'] / 10);

        return [
            'attack' => $this->attack,
            'healthPoints' => $this->healthPoints,
        ];
    }

    public function onMonsterPlayed(GameContext $context): void
    {
        $context->pushGameEvent(GameEventTypeEnum::UPDATE_CARD_STATE, [
            'cardId' => $this->getInstanceId(),
            'stateToUpdate' => [
                'attack' => $this->attack,
                'healthPoints' => $this->healthPoints,
            ],
            'currentHealthPoints' => $this->healthPoints,
        ]);
    }

    public function setComputedValue(mixed $value): void
    {
        if (is_array($value)) {
            $this->attack = (int) $value['attack'];
            $this->healthPoints = (int) $value['healthPoints'];
        }
    }
}
