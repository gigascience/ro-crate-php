<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the ContextualEntity class
 */
class Organization extends ContextualEntity
{
    /**
     * Constructs an instance of Organization type
     * @param string $id The ID of the organization entity
     */
    public function __construct(string $id)
    {
        parent::__construct($id, ['Organization']);
    }

    /**
     * Gets the information of the Organization entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array
    {
        return array_merge(parent::baseArray(), $this->properties);
    }
}
