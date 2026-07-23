<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Account Lockout
    |--------------------------------------------------------------------------
    |
    | FR-AUTH-006: accounts lock after this many consecutive failed logins,
    | for this many minutes.
    |
    */

    'max_login_attempts' => (int) env('AUTH_MAX_LOGIN_ATTEMPTS', 5),
    'lockout_minutes' => (int) env('AUTH_LOCKOUT_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | FR-AUTH-007: a new password may not match any of the last N passwords.
    | FR-AUTH-008: password age (days) after which rotation is forced. Set to
    | 0 to disable forced expiry.
    |
    */

    'password_history_count' => (int) env('AUTH_PASSWORD_HISTORY_COUNT', 5),
    'password_expiry_days' => (int) env('AUTH_PASSWORD_EXPIRY_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Roles exempt from forced password expiry
    |--------------------------------------------------------------------------
    |
    | The Guest role is exempt by default — guests are not staff and forcing
    | rotation on an infrequently-used self-service account is more likely to
    | lock guests out than to improve security.
    |
    */

    'password_expiry_exempt_roles' => ['Guest'],

    /*
    |--------------------------------------------------------------------------
    | Multi-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | FR-AUTH-004: MFA is optional per user, but can be made mandatory for
    | specific roles (e.g. Accountant, Super Administrator) by listing them
    | here.
    |
    */

    'mfa_required_roles' => array_filter(explode(',', (string) env('AUTH_MFA_REQUIRED_ROLES', ''))),
    'mfa_recovery_codes_count' => 8,
];
