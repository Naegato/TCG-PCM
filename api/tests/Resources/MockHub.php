<?php

declare(strict_types=1);

namespace App\Tests\Resources;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\Component\Mercure\Update;

final class MockHub implements HubInterface
{
    public function getFactory(): ?TokenFactoryInterface
    {
        return null;
    }

    public function getPublicUrl(): string
    {
        return 'http://example.com/.well-known/mercure';
    }

    public function publish(Update $update): string
    {
        return 'http://example.com/.well-known/mercure';
    }

    public function getProtocolVersion(): ProtocolVersion
    {
        return ProtocolVersion::Legacy;
    }

    public function getCookieName(): string
    {
        return ProtocolVersion::Legacy->getDefaultCookieName();
    }
}
