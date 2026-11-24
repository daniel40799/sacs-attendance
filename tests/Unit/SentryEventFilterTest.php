<?php

namespace Tests\Unit;

use App\Support\SentryEventFilter;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sentry\Event;
use Sentry\ExceptionDataBag;
use Sentry\UserDataBag;

class SentryEventFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Event::class)) {
            $this->markTestSkipped('sentry/sentry-laravel is not installed.');
        }
    }

    public function test_request_keeps_only_path_method_and_safe_headers(): void
    {
        $event = Event::createEvent();
        $event->setRequest([
            'url' => 'https://attendance.sacs.edu.ph/students?search=Juan+Dela+Cruz',
            'method' => 'POST',
            'data' => ['last_name' => 'Dela Cruz', 'birthdate' => '2012-01-01'],
            'cookies' => ['laravel_session' => 'secret'],
            'query_string' => 'search=Juan+Dela+Cruz',
            'env' => ['REMOTE_ADDR' => '203.0.113.5'],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0',
                'Cookie' => 'laravel_session=secret',
                'Authorization' => 'Bearer secret',
                'X-Forwarded-For' => '203.0.113.5',
            ],
        ]);

        $request = SentryEventFilter::scrub($event, null)->getRequest();

        $this->assertSame('https://attendance.sacs.edu.ph/students', $request['url']);
        $this->assertSame('POST', $request['method']);
        $this->assertSame(['User-Agent' => 'Mozilla/5.0'], $request['headers']);
        $this->assertArrayNotHasKey('data', $request);
        $this->assertArrayNotHasKey('cookies', $request);
        $this->assertArrayNotHasKey('query_string', $request);
        $this->assertArrayNotHasKey('env', $request);
    }

    public function test_user_is_reduced_to_the_id(): void
    {
        $event = Event::createEvent();
        $user = UserDataBag::createFromUserIdentifier(42);
        $user->setEmail('teacher@sacs.edu.ph');
        $user->setIpAddress('203.0.113.5');
        $event->setUser($user);

        $scrubbed = SentryEventFilter::scrub($event, 42)->getUser();

        $this->assertSame(42, $scrubbed->getId());
        $this->assertNull($scrubbed->getEmail());
        $this->assertNull($scrubbed->getIpAddress());
        $this->assertNull(SentryEventFilter::scrub(Event::createEvent(), null)->getUser());
    }

    public function test_sql_with_bindings_is_removed_from_exception_messages(): void
    {
        $event = Event::createEvent();
        $event->setExceptions([new ExceptionDataBag(new RuntimeException(
            "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'x' (SQL: insert into students (last_name) values (Dela Cruz))"
        ))]);

        $message = SentryEventFilter::scrub($event, null)->getExceptions()[0]->getValue();

        $this->assertStringStartsWith('SQLSTATE[23000]: Integrity constraint violation', $message);
        $this->assertStringEndsWith('(SQL omitted)', $message);
        $this->assertStringNotContainsString('Dela Cruz', $message);
    }

    public function test_laravel_10_connection_style_sql_is_removed_too(): void
    {
        $this->assertSame(
            'SQLSTATE[HY000]: General error (SQL omitted)',
            SentryEventFilter::withoutSql('SQLSTATE[HY000]: General error (Connection: mysql, SQL: select * from users where email = a@b.c)')
        );
        $this->assertSame('Plain message', SentryEventFilter::withoutSql('Plain message'));
    }
}
