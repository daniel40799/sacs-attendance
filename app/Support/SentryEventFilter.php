<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\UserDataBag;
use Throwable;

/**
 * Last stop before an event leaves for Sentry: removes anything personal.
 *
 * Keeps: exception type, message (minus SQL), stack trace, URL path, method,
 * user agent, and the numeric ID of the signed-in user.
 * Drops: request body, cookies, query string, other headers, IP, SQL text.
 */
class SentryEventFilter
{
    private const KEPT_HEADERS = ['user-agent', 'accept', 'content-type'];

    public static function beforeSend(Event $event, ?EventHint $hint = null): ?Event
    {
        try {
            $userId = Auth::id();
        } catch (Throwable) {
            $userId = null;
        }

        return self::scrub($event, $userId);
    }

    public static function scrub(Event $event, int|string|null $userId): Event
    {
        $request = $event->getRequest();

        if ($request !== []) {
            $headers = [];
            foreach (($request['headers'] ?? []) as $name => $value) {
                if (in_array(strtolower((string) $name), self::KEPT_HEADERS, true)) {
                    $headers[$name] = $value;
                }
            }

            $url = isset($request['url']) ? strtok((string) $request['url'], '?') : null;

            $event->setRequest(array_filter([
                'url' => $url === false ? null : $url,
                'method' => $request['method'] ?? null,
                'headers' => $headers,
            ], static fn ($value) => $value !== null && $value !== []));
        }

        $event->setUser($userId === null ? null : UserDataBag::createFromUserIdentifier($userId));

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue(self::withoutSql($exception->getValue()));
        }

        return $event;
    }

    /**
     * Laravel's QueryException message ends with "(SQL: ...)" or
     * "(Connection: x, SQL: ...)" with the bindings filled in, i.e. real
     * names and numbers. Keep the database error, drop the statement.
     */
    public static function withoutSql(string $message): string
    {
        return preg_replace('/\s*\((?:Connection: [^,()]+, )?SQL: .*\)\s*$/s', ' (SQL omitted)', $message) ?? $message;
    }
}
