<?php

declare(strict_types=1);

namespace App\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Api\DTO\CardDTO;
use App\Api\DTO\GameDataDTO;
use App\Domain\Model\CardEffect;
use App\Enum\CardEffectEnum;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Service\Game\CardRegistryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements ProviderInterface<GameDataDTO>
 */
final class GameDataProvider implements ProviderInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private CardRegistryInterface $cardRegistry,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GameDataDTO
    {
        return new GameDataDTO($this->getCardEffects(), $this->getCards());
    }

    /**
     * @return array<string, CardEffect>
     */
    private function getCardEffects(): array
    {
        return array_reduce(
            CardEffectEnum::cases(),
            function (array $acc, CardEffectEnum $cardEffect) {
                $name = $this->translator->trans(\sprintf('effects.%s.name', $cardEffect->value), [], 'game');
                $acc[$cardEffect->value] = new CardEffect($name, $this->translator->trans(\sprintf('effects.%s.description', $cardEffect->value), [], 'game'));

                return $acc;
            },
            [],
        );
    }

    /**
     * @return array<string, CardDTO>
     */
    private function getCards(): array
    {
        return array_reduce(
            $this->cardRegistry->getAllBy([]),
            function (array $acc, string $cardId) {
                $template = $this->cardRegistry->getCardTemplateById($cardId);

                $acc[$cardId] = new CardDTO(
                    name: $template->getName(),
                    description: $template->getDescription(),
                    image: $template->getImage(),
                    requiresTarget: $template instanceof AbstractConsumableCard && $template->requiresTarget(),
                    targetType: $template instanceof AbstractConsumableCard ? $template->getTargetType() : null,
                    rarity: $template->getRarity(),
                    set: $template->getSerie(),
                    instanceId: '',
                    effects: [],
                );

                return $acc;
            },
            [],
        );
    }
}
