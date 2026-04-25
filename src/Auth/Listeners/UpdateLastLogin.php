<?php

declare(strict_types=1);

namespace Strux\Auth\Listeners;

use App\Domain\General\Entity\User;
use DateTime;
use Strux\Auth\Events\UserLoggedIn;

class UpdateLastLogin
{
    public function handle(UserLoggedIn $event): void
    {
        /** @var User $user */
        $user = $event->user;

        try {
            $user->last_login_at = new DateTime();
            $user->save();
        } catch (\Throwable $e) {
            error_log('Failed to update last_login_at: ' . $e->getMessage());
        }
        /*if (property_exists($user, 'last_login_at') && method_exists($user, 'save')) {
            $user->last_login_at = new DateTime();
            $user->save();
        } else {
            error_log('last_login_at property does not exist');
        }*/
    }
}