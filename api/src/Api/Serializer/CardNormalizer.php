<?php

declare(strict_types=1);

namespace App\Api\Serializer;

use App\Api\DTO\CardDTO;
use App\Api\DTO\CollectionCardDTO;
use ArrayObject;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CardNormalizer implements NormalizerInterface
{
    private const string CARD_IMAGE_BASE_URL = 'cards/';

    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CardDTO || $data instanceof CollectionCardDTO;
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|ArrayObject|null
    {
        /** @var CardDTO|CollectionCardDTO $card */
        $card = $data;

        $path = $card->image;
        $type = $card->type;
        $rarity = $card->rarity;
        $serie = $card->set;
        $targetType = $card instanceof CardDTO ? $card->targetType : null;

        return [
            'name' => $card->name,
            'description' => $card->description,
            'type' => $type?->name,
            'typeLabel' => $type?->label()->trans($this->translator),
            'rarity' => $rarity->name,
            'rarityLabel' => $rarity->label()->trans($this->translator),
            'serie' => $serie->name,
            'serielabel' => $serie,
            'image' => filter_var($path, FILTER_VALIDATE_URL) ? $path : self::CARD_IMAGE_BASE_URL.strtolower($path),
            'requiresTarget' => $card instanceof CardDTO ? $card->requiresTarget : null,
            'targetType' => $targetType,
            'cost' => $card->cost,
            'hp' => $card->hp,
            'attack' => $card->attack,
            'instanceId' => $card->instanceId,
            'effects' => $card instanceof CardDTO ? $card->effects : null,
            'isActive' => $card instanceof CardDTO ? $card->isActive : null,
            'isNewToCollection' => $card instanceof CollectionCardDTO ? $card->isNewToCollection : null,
            'values' => $card instanceof CardDTO ? $card->values : null,
        ];
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            CardDTO::class => true,
            CollectionCardDTO::class => true,
        ];
    }
}
