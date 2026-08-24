<?php

namespace Workbench\App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SameOldNick\Ntfy\Concerns\NtfyNotifiable;
use Workbench\Workbench\Database\Factories\UserFactory;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use NtfyNotifiable {
        routeNotificationForNtfy as ntfyRouteNotification;
    }

    public ?array $ntfyRoute = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Route notifications for the ntfy channel.
     */
    public function routeNotificationForNtfy($notification = null)
    {
        return $this->ntfyRoute ?? $this->ntfyRouteNotification($notification);
    }
}
