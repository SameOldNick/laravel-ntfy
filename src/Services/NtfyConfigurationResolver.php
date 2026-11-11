<?php

namespace SameOldNick\Ntfy\Services;

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
        return $notifiable->morphOne(
            $this->getModelClass(),
            $this->getMorphName(),
            $this->getMorphType(),
            $this->getMorphId(),
            $this->getMorphLocalKey(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function resolveForNotifiable(object $notifiable)
    {
        if (! $notifiable instanceof Model) {
            throw new InvalidArgumentException('The notifiable must be an instance of Illuminate\Database\Eloquent\Model.');
        }

        return $this->resolveRelationshipForNotifiable($notifiable)->first();
    }
}
