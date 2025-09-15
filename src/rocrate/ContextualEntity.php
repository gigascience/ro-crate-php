<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the Entity class, refers to entities that describe data entities and can also be a data
 * entity in certain circumstances
 */
class ContextualEntity extends Entity
{
    /**
     * Constructs a contextual entity instance
     * @param string $id The ID of the contextual entity
     * @param array $types The type(s) of the contextual entity
     */
    public function __construct(string $id, array $types)
    {
        parent::__construct($id, $types);
    }

    /**
     * Gets the information of the contextual entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array
    {
        $data = parent::baseArray();
        return array_merge($data, $this->properties);
    }
}
