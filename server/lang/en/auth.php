<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    /* API Authentication Messages */
    'login_success' => 'Login successful.',
    'logout_success' => 'Logout successful.',
    'registration_success' => 'User registration successful.',
    'registration_failed' => 'User registration failed.',
    'already_logged_in' => 'Already logged in.',
    'invitation_token_created' => 'Invitation token created successfully.',
    'invitation_token_creation_failed' => 'Failed to create invitation token.',

    'email_verification_failed' => 'Email verification process failed.',
    'email_verification_notification' => [
        'store' => 'Email verification link resent.',
        'store_failed' => 'Failed to resend email verification link.',
        'store_not_found' => 'Email address not registered.',
    ],
];
