<?php

declare(strict_types=1);

namespace Strux\Auth\Listeners;

use Strux\Auth\Events\UserLoggedIn;
use DateTime;

class UpdateLastLogin
{
    public function handle(UserLoggedIn $event): void
    {
        $user = $event->user;

        // Ensure the user model has a save method and last_login_at property
        if (property_exists($user, 'last_login_at') && method_exists($user, 'save')) {
            $user->last_login_at = new DateTime();
            $user->save();
        } else {
            error_log('last_login_at does not exist');
        }
    }
}