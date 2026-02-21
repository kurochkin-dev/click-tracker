<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Events;

use App\Domain\Events\EventValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для EventValidator.
 *
 * Проверяет валидацию и нормализацию входных данных событий.
 */
class EventValidatorTest extends TestCase
{
    private EventValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new EventValidator();
    }

    /**
     * Проверяет, что валидное событие проходит валидацию без ошибок.
     */
    #[Test]
    public function validateReturnsNullForValidEvent(): void
    {
        $event = [
            'user_id'     => 'user123',
            'action'      => 'click',
            'campaign_id' => 'campaign456',
            'ts'          => 1700000000000,
        ];

        self::assertNull($this->validator->validate($event));
    }

    /**
     * Проверяет, что отсутствие обязательных полей возвращает ошибку missing_fields.
     *
     * @param array<string, mixed> $event
     * @param string[]             $missingFields
     */
    #[Test]
    #[DataProvider('missingFieldsProvider')]
    public function validateReturnsMissingFieldsError(array $event, array $missingFields): void
    {
        $result = $this->validator->validate($event);

        self::assertNotNull($result);
        self::assertSame('missing_fields', $result['msg']);
        self::assertEqualsCanonicalizing($missingFields, $result['fields']);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string[]}>
     */
    public static function missingFieldsProvider(): array
    {
        return [
            'нет user_id и action' => [[], ['user_id', 'action']],
            'нет user_id'          => [['action' => 'click'], ['user_id']],
            'нет action'           => [['user_id' => 'u1'], ['action']],
        ];
    }

    /**
     * Проверяет, что пустой user_id приводит к ошибке user_id_invalid.
     */
    #[Test]
    public function validateReturnsErrorForEmptyUserId(): void
    {
        $event = ['user_id' => '', 'action' => 'click'];

        $result = $this->validator->validate($event);

        self::assertNotNull($result);
        self::assertSame('user_id_invalid', $result['msg']);
    }

    /**
     * Проверяет, что нечисловой user_id (массив) приводит к ошибке user_id_invalid.
     */
    #[Test]
    public function validateReturnsErrorForNonStringUserId(): void
    {
        $event = ['user_id' => ['not', 'a', 'string'], 'action' => 'click'];

        $result = $this->validator->validate($event);

        self::assertNotNull($result);
        self::assertSame('user_id_invalid', $result['msg']);
    }

    /**
     * Проверяет, что пустой action приводит к ошибке action_invalid.
     */
    #[Test]
    public function validateReturnsErrorForEmptyAction(): void
    {
        $event = ['user_id' => 'user1', 'action' => ''];

        $result = $this->validator->validate($event);

        self::assertNotNull($result);
        self::assertSame('action_invalid', $result['msg']);
    }

    /**
     * Проверяет, что нечисловой campaign_id приводит к ошибке campaign_id_invalid.
     */
    #[Test]
    public function validateReturnsErrorForNonStringCampaignId(): void
    {
        $event = ['user_id' => 'u1', 'action' => 'click', 'campaign_id' => 12345];

        $result = $this->validator->validate($event);

        self::assertNotNull($result);
        self::assertSame('campaign_id_invalid', $result['msg']);
    }

    /**
     * Проверяет, что нечисловой ts приводит к ошибке ts_invalid.
     */
    #[Test]
    public function validateReturnsErrorForNonNumericTs(): void
    {
        $event = ['user_id' => 'u1', 'action' => 'click', 'ts' => 'not-a-number'];

        $result = $this->validator->validate($event);

        self::assertNotNull($result);
        self::assertSame('ts_invalid', $result['msg']);
    }

    /**
     * Проверяет, что необязательные поля campaign_id и ts не приводят к ошибке при их отсутствии.
     */
    #[Test]
    public function validateAcceptsEventWithoutOptionalFields(): void
    {
        $event = ['user_id' => 'user1', 'action' => 'impression'];

        self::assertNull($this->validator->validate($event));
    }

    /**
     * Проверяет, что normalize подставляет defaultTs при отсутствии ts.
     */
    #[Test]
    public function normalizeAddsDefaultTsWhenMissing(): void
    {
        $event     = ['user_id' => 'u1', 'action' => 'click'];
        $defaultTs = 1700000000000;

        $result = $this->validator->normalize($event, $defaultTs);

        self::assertSame($defaultTs, $result['ts']);
    }

    /**
     * Проверяет, что normalize сохраняет существующий числовой ts.
     */
    #[Test]
    public function normalizeKeepsExistingTs(): void
    {
        $existingTs = 1699999999000;
        $event      = ['user_id' => 'u1', 'action' => 'click', 'ts' => $existingTs];

        $result = $this->validator->normalize($event, 9999999999);

        self::assertSame($existingTs, $result['ts']);
    }

    /**
     * Проверяет, что normalize приводит числовую строку ts к int.
     */
    #[Test]
    public function normalizeConvertsNumericStringTsToInt(): void
    {
        $event = ['user_id' => 'u1', 'action' => 'click', 'ts' => '1700000000000'];

        $result = $this->validator->normalize($event, 0);

        self::assertSame(1700000000000, $result['ts']);
    }
}
