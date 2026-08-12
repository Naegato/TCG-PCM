<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Enum\CardSetEnum;
use App\Service\Game\CardRegistryInterface;
use App\Service\User\UserGenerateBoosterTokens;
use App\Tests\Functional\FunctionalTestCase;
use App\Tests\Resources\Fixtures\ThereIs;

final class UserApiTest extends FunctionalTestCase
{
    private const LOGIN_URI = '/api/login_check';
    private const INVENTORY_SET_STATS_URI = '/api/inventory/stats';
    private const CURRENT_USER_URI = '/api/user';

    private UserGenerateBoosterTokens $userGenerateBoosterTokens;

    public function setUp(): void
    {
        parent::setUp();

        $this->userGenerateBoosterTokens = static::getContainer()->get(UserGenerateBoosterTokens::class);
    }

    public function testLogin()
    {
        $this->createUser('onMangeDesPatesCeSoir', 'jyPeutRienJsuisEtudiant', true);

        $response = $this->client->request('POST', static::LOGIN_URI, [
            'json' => [
                'username' => 'onMangeDesPatesCeSoir',
                'password' => 'jyPeutRienJsuisEtudiant',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonHasKey('token', $response);
    }

    public function testLoginWithWrongCredentials()
    {
        $this->createUser('jaiFaimSVP', 'genreJeVeuxManger', true);

        $this->client->request('POST', static::LOGIN_URI, [
            'json' => [
                'username' => 'jaiFaimSVP',
                'password' => 'pasLeDroitDeManger',
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertJsonContains([
            'message' => 'Identifiants invalides.',
        ]);
    }

    public function testGetInventorySetStats()
    {
        $inventory = ThereIs::anInventory()->withCard('D6', 2)->withCard('Spicy-D6', 3)->withCard('Pierrot', 1)->build();
        $user = ThereIs::anUser()->withInventory($inventory)->build();
        $this->client->loginUser($user);

        /** @var CardRegistryInterface $cardRegistry */
        $cardRegistry = static::getContainer()->get(CardRegistryInterface::class);
        $totalTboiCards = count($cardRegistry->getAllBy(['serie' => CardSetEnum::TBOI]));
        $totalOriginalCards = count($cardRegistry->getAllBy(['serie' => CardSetEnum::ORIGINAL]));
        $totalBtd6Cards = count($cardRegistry->getAllBy(['serie' => CardSetEnum::BTD6]));

        $response = $this->get(static::INVENTORY_SET_STATS_URI);
        $content = $response->toArray();

        $statsBySet = [];
        foreach ($content as $setStats) {
            $statsBySet[$setStats['set']] = $setStats;
        }

        self::assertArrayHasKey('ORIGINAL', $statsBySet);
        self::assertArrayHasKey('TBOI', $statsBySet);
        self::assertArrayHasKey('BTD6', $statsBySet);

        self::assertSame(1, $statsBySet['ORIGINAL']['ownedCards']);
        self::assertSame($totalOriginalCards, $statsBySet['ORIGINAL']['totalCards']);
        self::assertSame(2, $statsBySet['TBOI']['ownedCards']);
        self::assertSame($totalTboiCards, $statsBySet['TBOI']['totalCards']);
        self::assertSame(0, $statsBySet['BTD6']['ownedCards']);
        self::assertSame($totalBtd6Cards, $statsBySet['BTD6']['totalCards']);
    }

    public function testGetCurrentUser()
    {
        $user = ThereIs::anUser()->build();
        $this->client->loginUser($user);

        $response = $this->get(static::CURRENT_USER_URI);
        $content = $response->toArray();

        self::assertSame($user->getUsername(), $content['username']);
    }

    public function testGetCurrentUserIsAdminFalseByDefault()
    {
        $user = ThereIs::anUser()->build();
        $this->client->loginUser($user);

        $response = $this->get(static::CURRENT_USER_URI);
        $content = $response->toArray();

        self::assertFalse($content['isAdmin']);
    }

    public function testGetCurrentUserIsAdminTrueForAdminRole()
    {
        $user = ThereIs::anUser()->asAdmin()->build();
        $this->client->loginUser($user);

        $response = $this->get(static::CURRENT_USER_URI);
        $content = $response->toArray();

        self::assertTrue($content['isAdmin']);
    }

    public function testGetCurrentUserTokens()
    {
        $user = ThereIs::anUser()->withBoosterTokens(100)->build();
        $this->client->loginUser($user);

        $response = $this->get(static::CURRENT_USER_URI);
        $content = $response->toArray();

        self::assertSame(100, $content['userWallet']['boosterTokens']);
    }

    public function testGenerateBoosterTokens()
    {
        $date = new \DateTimeImmutable();
        $twoDaysAgo = $date->modify('-2 days');
        $user = ThereIs::anUser()->withBoosterTokens(0)->withLastBoosterTokensAt($twoDaysAgo)->build();

        $this->userGenerateBoosterTokens->generate($user);

        self::assertSame(4, $user->getUserWallet()->getBoosterTokens());
    }

    public function testGenerateBoosterTokensSpareTimeKept()
    {
        $date = new \DateTimeImmutable();
        $daysAgo = $date->modify('-2 days -3 hours');
        $hoursAgo = $date->modify('-1 hour');

        $user = ThereIs::anUser()->withBoosterTokens(0)->withLastBoosterTokensAt($daysAgo)->build();

        $this->userGenerateBoosterTokens->generate($user);

        $delta = 1;
        self::assertEqualsWithDelta($hoursAgo->getTimestamp(), $user->getUserInfo()->getLastBoosterTokensAt()->getTimestamp(), $delta);
    }

    public function testGenerateBoosterTokensDoesNotResetClockWhenNothingIsEarned()
    {
        $date = new \DateTimeImmutable();
        $twoHoursAgo = $date->modify('-2 hours');

        $user = ThereIs::anUser()->withBoosterTokens(0)->withLastBoosterTokensAt($twoHoursAgo)->build();

        $this->userGenerateBoosterTokens->generate($user);

        self::assertSame(0, $user->getUserWallet()->getBoosterTokens());
        self::assertEqualsWithDelta($twoHoursAgo->getTimestamp(), $user->getUserInfo()->getLastBoosterTokensAt()->getTimestamp(), 1);
    }

    public function testGenerateBoosterTokensNotAboveCap()
    {
        $user = ThereIs::anUser()
            ->withBoosterTokens(3)
            ->withLastBoosterTokensAt(new \DateTimeImmutable('2023-01-01'))
            ->build();

        $this->userGenerateBoosterTokens->generate($user);

        self::assertSame(5, $user->getUserWallet()->getBoosterTokens());
    }
}
