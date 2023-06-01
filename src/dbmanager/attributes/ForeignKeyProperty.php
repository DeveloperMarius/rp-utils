<?php

namespace utils\dbmanager\attributes;

use Attribute;

/**
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ForeignKeyProperty
{

    /**
     * @param string|null $references
     * @param bool $cascade_on_delete
     */
    public function __construct(private readonly ?string $references = null, private readonly bool $cascade_on_delete = true){}

    /**
     * @return string|null
     */
    public function getReferences(): ?string{
        return $this->references;
    }

    /**
     * @return bool
     */
    public function isCascadeOnDelete(): bool{
        return $this->cascade_on_delete;
    }
}