<?php

namespace App\Debug;

use App\Enum\GameEventTypeEnum;
use App\Game\State\GameEvent;

readonly class TraceableGameEvent extends GameEvent
{
    public function __construct(
        public string $origin,
        int $id,
        GameEventTypeEnum $type,
        string $eventOrigin,
        array $data,
        ?int $parentId = null,
        int $localId = 0,
    ) {
        parent::__construct($id, $type, $eventOrigin, $data, $parentId, $localId);
    }

    public static function fromParent(parent $event, string $origin): self
    {
        return new self($origin, $event->id, $event->type, $event->eventOrigin, $event->data, $event->parentId, $event->localId);
    }
}
