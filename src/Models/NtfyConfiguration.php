<?php

namespace SameOldNick\Ntfy\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use SameOldNick\Ntfy\DTOs\ServerInfo;
use SameOldNick\Ntfy\Enums\AuthMethod;

/**
 * @property int $id
 * @property string $server_url
 * @property string $topic
 * @property string|null $auth_token
 * @property string|null $username
 * @property string|null $password
 * @property AuthMethod $auth_method
 */
class NtfyConfiguration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'server_url',
        'topic',
        'auth_token',
        'username',
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'server_url' => 'string',
        'topic' => 'string',
        'auth_token' => 'encrypted',
        'username' => 'encrypted',
        'password' => 'encrypted',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'auth_token',
        'username',
        'password',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['auth_method'];

    /**
     * Get the owning notifiable model.
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Get the authentication method based on the presence of credentials.
     *
     * @return Attribute<AuthMethod::Login|AuthMethod::None|AuthMethod::Token, never>
     */
    protected function authMethod(): Attribute
    {
        return Attribute::get(fn (mixed $value, array $attributes): AuthMethod => match (true) {
            ! empty($attributes['username']) || ! empty($attributes['password']) => AuthMethod::Login,
            ! empty($attributes['auth_token']) => AuthMethod::Token,
            default => AuthMethod::None,
        });
    }

    /**
     * Convert the configuration to a ServerInfo DTO.
     */
    public function toServerInfo(): ServerInfo
    {
        return match ($this->auth_method) {
            AuthMethod::Login => ServerInfo::createWithAuth(
                url: $this->server_url,
                topic: $this->topic,
                username: $this->username,
                password: $this->password,
            ),
            AuthMethod::Token => ServerInfo::createWithToken(
                url: $this->server_url,
                topic: $this->topic,
                token: $this->auth_token,
            ),
            default => ServerInfo::createWithoutAuth(
                url: $this->server_url,
                topic: $this->topic,
            ),
        };
    }
}
