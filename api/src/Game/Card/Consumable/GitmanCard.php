<?php

namespace App\Game\Card\Consumable;

use App\Enum\CardRarityEnum;
use App\Game\Card\Interface\ComputedCardInterface;
use App\Game\GameContext;
use App\Game\GameUtils;
use App\Service\Game\Helper\HttpHelper;

class GitmanCard extends AbstractConsumableCard implements ComputedCardInterface
{
    public static CardRarityEnum $rarity = CardRarityEnum::EPIC;

    private const string GITHUB_API_URL = 'https://api.github.com/repos/Naegato/TCG-PCM/commits?per_page=1';

    private const int DAMAGE_MULTIPLIER = 1;

    private const int DAMAGE_DIVISOR = 10;

    private int $commitCount = 0;

    public function getId(): string
    {
        return 'Gitman';
    }

    public function getImage(): string
    {
        return 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Git-logo.svg';
    }

    public function getDescription(): string
    {
        return GameUtils::formatDescription(parent::getDescription(), [
            'value' => self::DAMAGE_MULTIPLIER,
            'divisor' => self::DAMAGE_DIVISOR,
        ]);
    }

    public function play(GameContext $context, array $data = []): void
    {
        $value = fn() => (int) round(($this->getValue(self::DAMAGE_MULTIPLIER, true) * $this->getCommitCount()) / self::DAMAGE_DIVISOR);

        $value = $context->runtimeValueEffect($value);

        if (!\is_int($value)) {
            throw new \RuntimeException('Expected an integer value for GitmanCard damage calculation.');
        }
        $context->attack($value);
    }

    public function computeValue(): mixed
    {
        return $this->getCommitCount();
    }

    public function setComputedValue(mixed $value): void
    {
        $this->commitCount = (int) $value;
    }

    protected function getCommitCount(): int
    {
        if ($this->commitCount > 0) {
            return $this->commitCount;
        }

        /** @var HttpHelper $client */
        $client = GameUtils::getService('http');
        $response = $client->get(self::GITHUB_API_URL);

        $headers = $response->getHeaders();

        if ($linkHeader = $headers['link'][0] ?? null) {
            $matches = [];
            preg_match('/&page=(\d+)>; rel="last"/', $linkHeader, $matches);
            if ($matches[1]) {
                return (int) $matches[1];
            }
        }

        throw new \RuntimeException('Unable to fetch commit count from GitHub API');
    }
}
