<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Game\InitialGameState;
use App\Service\Game\State\GameStateProvider;
use App\Tests\Functional\FunctionalTestCase;
use App\Tests\Resources\Fixtures\ThereIs;
use App\Tests\Unit\Fixtures\DummyCard;

final class GameDebugApiTest extends FunctionalTestCase
{
    protected const string DEBUG_GET_URI = '/api/game/{id}/debug';
    protected const string DEBUG_POST_URI = '/api/game/{id}/debug';

    public function setup(): void
    {
        self::bootKernel(['debug' => true]);
        parent::setup();
    }

    public function testDebugGetForbiddenForNonAdmin(): void
    {
        $gameState = ThereIs::aGame()->withOwner($this->currentUser)->build();

        $this->get($this->getUri(self::DEBUG_GET_URI, ['id' => $gameState->getId()]));

        self::assertResponseStatusCodeSame(403);
    }

    public function testDebugPostForbiddenForNonAdmin(): void
    {
        $gameState = ThereIs::aGame()->withOwner($this->currentUser)->build();

        $this->post($this->getUri(self::DEBUG_POST_URI, ['id' => $gameState->getId()]), [
            'actionId' => 'force_end_turn',
            'payload' => [],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testDebugGetReturnsBothHandsUnfiltered(): void
    {
        $gameState = ThereIs::aGame()->withOwner($this->currentUser)->build();
        $this->loginAsAdmin();

        $response = $this->get($this->getUri(self::DEBUG_GET_URI, ['id' => $gameState->getId()]));

        self::assertResponseIsSuccessful();
        $cards = $response->toArray()['state']['cards'];

        self::assertArrayHasKey('1', $cards);
        self::assertArrayHasKey('cardtest', $cards);
    }

    public function testDebugGiveCard(): void
    {
        $gameState = ThereIs::aGame()->withOwner($this->currentUser)->build();
        $playerId = $gameState->getPlayer1State()->player->id;
        $this->loginAsAdmin();

        $this->post($this->getUri(self::DEBUG_POST_URI, ['id' => $gameState->getId()]), [
            'actionId' => 'give_card',
            'payload' => [
                'playerId' => $playerId,
                'cardTemplateId' => 'D6',
            ],
        ]);

        self::assertResponseIsSuccessful();

        $state = $this->getPersistedGameState($gameState->getId());

        self::assertCount(3, $state->cards);
    }

    public function testDebugSetStats(): void
    {
        $gameState = ThereIs::aGame()->withOwner($this->currentUser)->build();
        $playerId = $gameState->getPlayer1State()->player->id;
        $this->loginAsAdmin();

        $this->post($this->getUri(self::DEBUG_POST_URI, ['id' => $gameState->getId()]), [
            'actionId' => 'set_stats',
            'payload' => [
                'playerId' => $playerId,
                'healthPoints' => 50,
                'coins' => 20,
            ],
        ]);

        self::assertResponseIsSuccessful();

        $state = $this->getPersistedGameState($gameState->getId());

        self::assertSame(50, $state->player1->healthPoints);
        self::assertSame(20, $state->player1->coins);
    }

    public function testDebugForceEndTurn(): void
    {
        $gameState = ThereIs::aGame()->withOwner($this->currentUser)->build();
        $before = $gameState->getPlayer1State()->player->id;
        $this->givePlayer2ANonEmptyDrawPile($gameState);
        $this->loginAsAdmin();

        $this->post($this->getUri(self::DEBUG_POST_URI, ['id' => $gameState->getId()]), [
            'actionId' => 'force_end_turn',
            'payload' => [],
        ]);

        self::assertResponseIsSuccessful();

        $state = $this->getPersistedGameState($gameState->getId());

        self::assertNotSame($before, $state->currentPlayer);
    }

    public function testDebugForceSetCurrentPlayer(): void
    {
        $gameState = ThereIs::aGame()->withOwner($this->currentUser)->build();
        $player2Id = $gameState->getPlayer2State()->player->id;
        $this->loginAsAdmin();

        $this->post($this->getUri(self::DEBUG_POST_URI, ['id' => $gameState->getId()]), [
            'actionId' => 'force_set_current_player',
            'payload' => [
                'playerId' => $player2Id,
            ],
        ]);

        self::assertResponseIsSuccessful();

        $state = $this->getPersistedGameState($gameState->getId());

        self::assertSame($player2Id, $state->currentPlayer);
    }

    public function testDebugRemoveCard(): void
    {
        $gameState = ThereIs::aGame()->withOwner($this->currentUser)->build();
        $this->loginAsAdmin();

        $this->post($this->getUri(self::DEBUG_POST_URI, ['id' => $gameState->getId()]), [
            'actionId' => 'remove_card',
            'payload' => [
                'cardId' => '1',
            ],
        ]);

        self::assertResponseIsSuccessful();

        $state = $this->getPersistedGameState($gameState->getId());

        self::assertContains(DummyCard::class, $state->player2->discardPile);
    }

    private function givePlayer2ANonEmptyDrawPile(InitialGameState $gameState): void
    {
        $player2 = $gameState->getPlayer2State()->withNewHandAndDeck($gameState->getPlayer2State()->hand, ['drawn-card' => DummyCard::class]);

        $newGameState = new InitialGameState($gameState->getId(), $gameState->getSeed(), $gameState->getPlayer1State(), $player2, $gameState->getCards());

        $em = $this->getEm();
        $em->remove($gameState);
        $em->flush();
        $em->persist($newGameState);
        $em->flush();
    }

    private function getPersistedGameState(string $gameId): object
    {
        return static::getContainer()->get(GameStateProvider::class)->get($gameId);
    }

    private function loginAsAdmin(): void
    {
        $admin = ThereIs::anUser()->asAdmin()->build();
        $this->client->loginUser($admin, 'api');
    }
}
