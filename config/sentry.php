<?php

/*
 * Sentry error tracking.
 *
 * Off unless SENTRY_LARAVEL_DSN is set, so local, testing and CI never send
 * anything. Errors only: no tracing, profiling or log shipping (free plan).
 *
 * Privacy: this app holds data about minors. Nothing personal is sent:
 * send_default_pii is off, and App\Support\SentryEventFilter strips request
 * bodies, cookies, query strings, most headers and SQL bindings, and attaches
 * only the numeric user ID. Organization-level scrubbing and "don't store IP
 * addresses" are also enabled in Sentry (sacs-lk).
 *
 * The package's own defaults are merged underneath this file.
 */

return [

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    'release' => env('SENTRY_RELEASE'),

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),

    'sample_rate' => env('SENTRY_SAMPLE_RATE') === null ? 1.0 : (float) env('SENTRY_SAMPLE_RATE'),

    'traces_sample_rate' => null,

    'profiles_sample_rate' => null,

    'send_default_pii' => false,

    'before_send' => [App\Support\SentryEventFilter::class, 'beforeSend'],

    'breadcrumbs' => [
        'logs' => false,
        'cache' => false,
        'livewire' => false,
        'sql_queries' => false,
        'sql_bindings' => false,
        'sql_transaction' => false,
        'queue_info' => true,
        'command_info' => true,
        'http_client_requests' => false,
        'notifications' => false,
    ],

];
