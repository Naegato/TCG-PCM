<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Functional\FunctionalTestCase;
use App\Tests\Resources\Fixtures\ThereIs;

final class InventoryApiTest extends FunctionalTestCase
{
    private const string STATS_URI = '/api/inventory/stats';
    private const string COLLECTION_URI = '/api/inventory/collection';

    public function testGetInventoryStats(): void
    {
        $user = ThereIs::anUser()->build();
        $this->client->loginUser($user);

        $this->get(self::STATS_URI);

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();

        $data = json_decode($response->getContent() ?? '', true);
        self::assertIsArray($data);
        self::assertArrayHasKey('set', $data[0]);
        self::assertArrayHasKey('ownedCards', $data[0]);
        self::assertArrayHasKey('totalCards', $data[0]);
    }

    public function testGetCollectionReturnsAllCards(): void
    {
        $user = ThereIs::anUser()->build();
        $this->client->loginUser($user);

        $this->get(self::COLLECTION_URI);

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();

        $data = json_decode($response->getContent() ?? '', true);
        self::assertArrayHasKey('entries', $data);
        self::assertNotCount(0, $data['entries']);

        foreach ($data['entries'] as $entry) {
            self::assertArrayHasKey('card', $entry);
            self::assertArrayHasKey('quantity', $entry);
            self::assertArrayHasKey('name', $entry['card']);
            self::assertArrayHasKey('rarity', $entry['card']);
            self::assertArrayHasKey('type', $entry['card']);
            self::assertArrayHasKey('serie', $entry['card']);
            self::assertIsInt($entry['quantity']);
        }
    }

    public function testGetCollectionMarksOwnedCardsWithQuantity(): void
    {
        $user = ThereIs::anUser()->build();
        ThereIs::anInventory()->for($user)->withCard('Isaac', 3)->build();
        $this->client->loginUser($user);

        $this->get(self::COLLECTION_URI);

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();

        $data = json_decode($response->getContent() ?? '', true);

        $ownedEntries = array_filter($data['entries'], static fn(array $entry): bool => 'Isaac' === $entry['card']['instanceId']);
        $ownedEntry = array_values($ownedEntries)[0] ?? null;
        self::assertNotNull($ownedEntry);
        self::assertSame(3, $ownedEntry['quantity']);

        $otherEntries = array_filter($data['entries'], static fn(array $entry): bool => 'Isaac' !== $entry['card']['instanceId']);
        foreach ($otherEntries as $entry) {
            self::assertSame(0, $entry['quantity']);
        }
    }
}
