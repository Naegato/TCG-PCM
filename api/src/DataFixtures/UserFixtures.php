<?php

namespace App\DataFixtures;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends AbstractFixtures
{
    private const USER_PASSWORD = 'aaa';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct(User::class);
    }

    protected function getData(): iterable
    {
        yield [
            'username' => 'admin',
            'email' => 'admin@kard.fr',
            'password' => self::USER_PASSWORD,
            'roles' => ['ROLE_ADMIN'],
        ];

        yield [
            'username' => 'user',
            'email' => 'user@gmail.fr',
            'password' => self::USER_PASSWORD,
        ];
    }

    protected function postInstantiate($entity): void
    {
        $entity->setPassword($this->passwordHasher->hashPassword($entity, $entity->getPassword()));
    }
}
