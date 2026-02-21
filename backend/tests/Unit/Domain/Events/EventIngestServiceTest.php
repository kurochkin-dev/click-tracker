<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Events;

use App\Domain\Events\EventIngestService;
use App\Domain\Events\EventValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Redis;

/**
 * Тесты для EventIngestService.
 *
 * Проверяет логику приёма событий: валидацию, публикацию в Redis Streams и идемпотентность.
 */
class EventIngestServiceTest extends TestCase
{
    /** @var Redis&MockObject */
    private Redis $redis;

    private EventValidator $validator;
    private EventIngestService $service;

    protected function setUp(): void
    {
        $this->redis     = $this->createMock(Redis::class);
        $this->validator = new EventValidator();

        $this->service = new EventIngestService(
            redis:   $this->redis,
            validator: $this->validator,
            stream:  'events:ingest',
            maxlen:  100000,
        );
    }

    /**
     * Проверяет, что валидные события принимаются и записываются в Redis Stream.
     */
    #[Test]
    public function ingestAcceptsValidEvents(): void
    {
        $events = [
            ['user_id' => 'user1', 'action' => 'click', 'campaign_id' => 'camp1', 'ts' => 1700000000000],
            ['user_id' => 'user2', 'action' => 'impression', 'campaign_id' => 'camp1', 'ts' => 1700000001000],
        ];

        $this->redis
            ->expects(self::exactly(2))
            ->method('xAdd')
            ->willReturn('1700000000000-0');

        $result = $this->service->ingest($events);

        self::assertSame(2, $result['accepted']);
        self::assertSame(0, $result['skipped']);
        self::assertEmpty((array) $result['errors']);
        self::assertFalse($result['idempotent']);
    }

    /**
     * Проверяет, что невалидные события попадают в errors и не пишутся в Redis.
     */
    #[Test]
    public function ingestSkipsInvalidEventsAndReportsErrors(): void
    {
        $events = [
            ['user_id' => '', 'action' => 'click'],
            ['user_id' => 'user2', 'action' => 'click'],
        ];

        $this->redis
            ->expects(self::once())
            ->method('xAdd')
            ->willReturn('123-0');

        $result = $this->service->ingest($events);

        self::assertSame(1, $result['accepted']);
        self::assertSame(1, $result['skipped']);
        self::assertArrayHasKey(0, $result['errors']);
    }

    /**
     * Проверяет, что при всех невалидных событиях xAdd не вызывается.
     */
    #[Test]
    public function ingestReturnsZeroAcceptedWhenAllEventsInvalid(): void
    {
        $events = [
            ['action' => 'click'],
            ['user_id' => 'u1'],
        ];

        $this->redis->expects(self::never())->method('xAdd');

        $result = $this->service->ingest($events);

        self::assertSame(0, $result['accepted']);
        self::assertSame(2, $result['skipped']);
        self::assertFalse($result['idempotent']);
    }

    /**
     * Проверяет, что повторный запрос с тем же idempotency key возвращает idempotent=true.
     */
    #[Test]
    public function ingestReturnsIdempotentOnDuplicateKey(): void
    {
        $events = [['user_id' => 'u1', 'action' => 'click']];

        $this->redis
            ->method('set')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->redis->method('xAdd')->willReturn('123-0');

        $this->service->ingest($events, 'unique-key-abc');

        $result = $this->service->ingest($events, 'unique-key-abc');

        self::assertTrue($result['idempotent']);
        self::assertSame(0, $result['accepted']);
    }

    /**
     * Проверяет, что первый запрос с idempotency key проходит нормально (idempotent=false).
     */
    #[Test]
    public function ingestAcceptsFirstRequestWithIdempotencyKey(): void
    {
        $events = [['user_id' => 'u1', 'action' => 'click']];

        $this->redis->method('set')->willReturn(true);
        $this->redis->method('xAdd')->willReturn('123-0');

        $result = $this->service->ingest($events, 'first-time-key');

        self::assertFalse($result['idempotent']);
        self::assertSame(1, $result['accepted']);
    }

    /**
     * Проверяет, что пустой idempotency key игнорируется (не проверяется в Redis).
     */
    #[Test]
    public function ingestIgnoresEmptyIdempotencyKey(): void
    {
        $events = [['user_id' => 'u1', 'action' => 'click']];

        $this->redis->expects(self::never())->method('set');
        $this->redis->method('xAdd')->willReturn('123-0');

        $result = $this->service->ingest($events, '');

        self::assertFalse($result['idempotent']);
        self::assertSame(1, $result['accepted']);
    }

    /**
     * Проверяет, что событие без ts нормализуется (добавляется текущий timestamp).
     */
    #[Test]
    public function ingestNormalizesEventWithoutTs(): void
    {
        $events = [['user_id' => 'u1', 'action' => 'click']];

        $capturedPayload = null;

        $this->redis
            ->method('xAdd')
            ->willReturnCallback(function (string $stream, string $id, array $fields) use (&$capturedPayload): string {
                $capturedPayload = json_decode($fields['payload'], true);
                return '123-0';
            });

        $before = (int) floor(microtime(true) * 1000);
        $this->service->ingest($events);
        $after = (int) floor(microtime(true) * 1000);

        self::assertNotNull($capturedPayload);
        self::assertArrayHasKey('ts', $capturedPayload);
        self::assertGreaterThanOrEqual($before, $capturedPayload['ts']);
        self::assertLessThanOrEqual($after, $capturedPayload['ts']);
    }
}
