<?php

namespace SameOldNick\Ntfy\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface ResolvesNtfyConfiguration
{
    /**
     * Get the model class for the ntfy configuration.
     *
     * @return class-string<Model>
     */
    public function getModelClass(): string;

    /**
     * Get the morph name for the ntfy configuration.
     */
    public function getMorphName(): string;

    /**
     * Get the morph type for the ntfy configuration.
     */
    public function getMorphType(): ?string;

    /**
     * Get the morph ID for the ntfy configuration.
     */
    public function getMorphId(): ?string;

    /**
     * Get the morph local key for the ntfy configuration.
     */
    public function getMorphLocalKey(): ?string;

    /**
     * Resolve the ntfy configuration relationship for a given notifiable model.
     *
     * @return MorphOne<Model, covariant Model>
     */
    public function resolveRelationshipForNotifiable(Model $notifiable);

    /**
     * Resolve the ntfy configuration for a given notifiable model.
     *
     * @return Model|null The ntfy configuration model instance or null if not found.
     */
    public function resolveForNotifiable(object $notifiable);
}
