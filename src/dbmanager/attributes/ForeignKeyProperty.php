<?php

namespace utils\dbmanager\attributes;

use Attribute;
use JetBrains\PhpStorm\ExpectedValues;

/**
 *
 * @since 8.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ForeignKeyProperty
{

    /**
     * @param string|null $references_column
     * @param string|null $references_table
     * @param string|null $on_delete
     * @param string|null $on_update
     */
    public function __construct(private readonly ?string $references_column = null, private readonly ?string $references_table = null, #[ExpectedValues([null, 'CASCADE'])] private readonly ?string $on_delete = null, private readonly ?string $on_update = null){}

    /**
     * @return string|null
     */
    public function getReferencesColumn(): ?string{
        return $this->references_column;
    }

    /**
     * @return string|null
     */
    public function getReferencesTable(): ?string{
        return $this->references_table;
    }

    /**
     * @return string|null
     */
    public function getOnDelete(): ?string{
        return $this->on_delete;
    }

    /**
     * @return string|null
     */
    public function getOnUpdate(): ?string{
        return $this->on_update;
    }
}