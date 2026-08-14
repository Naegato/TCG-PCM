<?php

declare(strict_types=1);

namespace App\Debug;

use App\Debug\Card\TraceableCardFactory;
use App\Debug\GameContext\TraceableGameContextFactory;
use App\Enum\GameEventTypeEnum;
use App\Game\AbstractCard;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayArea;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\Data;
use Throwable;

final class GameDataCollector extends DataCollector
{
    public function __construct(
        private TraceableGameEventApplier $gameEventApplier,
        private TraceableGameContextFactory $gameContextFactory,
        private TraceableCardFactory $cardFactory,
    ) {}

    public function getName(): string
    {
        return 'game';
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        if (!$this->gameEventApplier->hasEvents() && !$this->gameContextFactory->hasGameContexts() && !$this->cardFactory->hasCards()) {
            return;
        }

        $events = [];
        $batch = 0;
        $isReplayBatch = false;

        foreach ($this->gameEventApplier->getEvents() as $traceableEvent) {
            // localId is only unique within a single GameEventResolver::resolve() call — a replay
            // catch-up (GameStateRebuilder) calls resolve() once per historical event on the same
            // resolver instance, so localId restarts at 1 for each one. Detecting that restart lets
            // us group events by the resolve() call they actually belong to for display purposes.
            // Whether a batch is a replay is decided by its root (localId === 1): it carries a real
            // persisted id when it comes from GameStateRebuilder, unlike a fresh live action. Every
            // event in that batch — reactions included — inherits the same flag, since only the root
            // of a live action ever has id === 0.
            if (1 === $traceableEvent->localId) {
                ++$batch;
                $isReplayBatch = 0 !== $traceableEvent->id;
            }

            $events[] = DebugGameEvent::fromTraceableGameEvent($traceableEvent, $batch, $isReplayBatch);
        }

        $mainEvent = null;
        $subEvents = [];

        foreach ($events as $event) {
            if ($event->isReplayEvent) {
                continue;
            }

            if (1 === $event->localId) {
                $mainEvent = $event;
                continue;
            }

            $subEvents[] = $event;
        }

        $this->data['mainEvent'] = $mainEvent;
        $this->data['subEvents'] = $subEvents;

        $this->data['stats'] = [
            'Player event' => count(array_filter($events, static fn(DebugGameEvent $event) => GameEvent::PLAYER_EVENT === $event->eventOrigin)),
            'Game event' => count(array_filter($events, static fn(DebugGameEvent $event) => GameEvent::GAME_EVENT === $event->eventOrigin)),
            'Replay event' => count(array_filter($events, static fn(DebugGameEvent $event) => $event->isReplayEvent)),
            'Total' => count($events),
        ];

        $this->data['events'] = $events;
        $this->data['gameContexts'] = $this->gameContextFactory->getGameContexts();

        $this->data['cards'] = $this->cardFactory->getCards();

        $this->data['lastGameState'] = $this->gameEventApplier->getLastGameState();
    }

    /**
     * @return Data|GameEvent[]
     */
    public function getEvents(): array
    {
        return $this->data['events'] ?? [];
    }

    public function getEventsCount(): int
    {
        return count($this->getEvents());
    }

    public function getEventStats(): array
    {
        return $this->data['stats'] ?? [];
    }

    /**
     * @return Data|GameEvent[]
     */
    public function getGameContexts(): Data|array
    {
        return array_map(fn($a) => [
            'state' => $this->cloneVar($a->state),
            'flushedEvents' => $this->cloneVar($a->flushedEvents),
        ], $this->data['gameContexts'] ?? []);
    }

    public function getMainEvent(): ?DebugGameEvent
    {
        return $this->data['mainEvent'] ?? null;
    }

    /**
     * @return Data|GameEvent[]
     */
    public function getSubEvents(): array
    {
        return $this->data['subEvents'] ?? [];
    }

    /**
     * @return Data|AbstractCard[]
     */
    public function getCards(): array
    {
        return $this->data['cards'] ?? [];
    }

    public function getLastGameState(): ?GameState
    {
        return $this->data['lastGameState'] ?? null;
    }

    public function getFullLastGameState(): ?Data
    {
        return $this->cloneVar($this->data['lastGameState']) ?? null;
    }

    public function getLastCards(): Data|GameState|null
    {
        return $this->cloneVar($this->data['lastGameState']->cards) ?? null;
    }

    public function getPlayArea(string $playerId): Data|PlayArea|null
    {
        $playArea = $this->data['lastGameState']->getPlayer($playerId)->playArea ?? null;

        return $playArea ? $this->cloneVar($playArea) : null;
    }

    public function getReplayedEvents(): array
    {
        return array_filter($this->data['events'], static fn(DebugGameEvent $e) => $e->isReplayEvent);
    }

    public function getRealEvents(): array
    {
        return array_filter($this->data['events'], static fn(DebugGameEvent $e) => !$e->isReplayEvent);
    }

    /**
     * Groups events by the resolve() call (batch) they belong to, preserving order — one entry
     * per batch, each holding its root (localId === 1) and every event it caused.
     *
     * @return array<int, DebugGameEvent[]>
     */
    public function getEventsGroupedByBatch(): array
    {
        $grouped = [];

        foreach ($this->getEvents() as $event) {
            $grouped[$event->batch][] = $event;
        }

        return $grouped;
    }
}

readonly class DebugGameEvent
{
    public function __construct(
        public int $id,
        public int $localId,
        public int $batch,
        public ?int $parentId,
        public string $origin,
        public GameEventTypeEnum $type,
        public string $eventOrigin,
        public array $data,
        public bool $shouldBePersisted,
        public bool $isReplayEvent,
    ) {}

    public static function fromTraceableGameEvent(TraceableGameEvent $event, int $batch, bool $isReplayEvent): self
    {
        return new self(
            $event->id,
            $event->localId,
            $batch,
            $event->parentId,
            $event->origin,
            $event->type,
            $event->eventOrigin,
            $event->data,
            $event->shouldBePersisted(),
            $isReplayEvent,
        );
    }
}
