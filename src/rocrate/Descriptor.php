<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the ContextualEntity class and Is a special contextual entity
 */
class Descriptor extends ContextualEntity
{
    /**
     * Constructs a metadata descriptor instance
     * @param string $id The ID of the metadata descriptor
     */
    public function __construct(string $id = 'ro-crate-metadata.json')
    {
        parent::__construct($id, ['CreativeWork']);
    }

    /**
     * Gets the information of the metadata descriptor entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array
    {
        return array_merge(parent::baseArray(), $this->properties);
    }
}
