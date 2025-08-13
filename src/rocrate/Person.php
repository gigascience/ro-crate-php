<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the ContextualEntity class
 */
class Person extends ContextualEntity
{
    /**
     * Constructs an instance of Person type
     * @param string $id The ID of the person entity
     */
    public function __construct(string $id)
    {
        parent::__construct($id, ['Person']);
    }

    /**
     * Gets the information of the Person entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array
    {
        return array_merge(parent::baseArray(), $this->properties);
    }
}
