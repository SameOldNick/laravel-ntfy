<?php

namespace SameOldNick\Ntfy\Services;

use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use InvalidArgumentException;
use SameOldNick\Ntfy\Contracts\ResolvesNtfyConfiguration;
use SameOldNick\Ntfy\Models\NtfyConfiguration;

class NtfyConfigurationResolver implements ResolvesNtfyConfiguration
{
    /**
     * The configuration model class.
     *
     * @var class-string<Model>
     */
    public readonly string $model;

    /**
     * Create a new NtfyConfigurationResolver instance.
     */
    public function __construct(
        #[Config('ntfy.model.class', NtfyConfiguration::class)]
        string $model = NtfyConfiguration::class,
        #[Config('ntfy.model.name', 'notifiable')]
        public readonly string $name = 'notifiable',
        #[Config('ntfy.model.type')]
        public readonly ?string $type = null,
        #[Config('ntfy.model.id')]
        public readonly ?string $id = null,
        #[Config('ntfy.model.local_key')]
        public readonly ?string $localKey = null,
        #[Config('ntfy.model.default', false)]
        public readonly mixed $default = false,
    ) {
        if (! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException(sprintf(
                'The configured ntfy model [%s] must be a subclass of %s.',
                $model,
                Model::class,
            ));
        }

        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getModelClass(): string
    {
        return $this->model;
    }

    /**
     * {@inheritDoc}
     */
    public function getMorphName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getMorphType(): ?string
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getMorphId(): ?string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getMorphLocalKey(): ?string
    {
        return $this->localKey;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveRelationshipForNotifiable(Model $notifiable): MorphOne
    {
        $morph = $notifiable->morphOne(
            $this->getModelClass(),
            $this->getMorphName(),
            $this->getMorphType(),
            $this->getMorphId(),
            $this->getMorphLocalKey(),
        );

        if ($this->default instanceof Closure) {
            $morph->withDefault(fn ($related, $parent) => $this->validateDefault(
                ($this->default)($related, $parent),
            ));
        } elseif (is_callable($this->default) || is_string($this->default)) {
            $morph->withDefault(fn ($related, $parent) => $this->validateDefault(
                app()->call($this->default, ['related' => $related, 'parent' => $parent]),
            ));
        } elseif ($this->default) {
            // `true` yields an empty instance and arrays are applied with forceFill().
            $morph->withDefault($this->default);
        }

        return $morph;
    }

    /**
     * Ensure a default callback returned a model, or a falsy value to fall back to the generated default.
     *
     * @throws InvalidArgumentException
     */
    protected function validateDefault(mixed $default): mixed
    {
        if ($default === null || $default === false || $default instanceof Model) {
            return $default;
        }

        throw new InvalidArgumentException(sprintf(
            'The ntfy model default callback must return an instance of %s, [%s] returned.',
            Model::class,
            get_debug_type($default),
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function resolveForNotifiable(object $notifiable)
    {
        if (! $notifiable instanceof Model) {
            throw new InvalidArgumentException('The notifiable must be an instance of Illuminate\Database\Eloquent\Model.');
        }

        return $this->resolveRelationshipForNotifiable($notifiable)->getResults();
    }
}
