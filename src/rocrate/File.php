<?php

namespace ROCrate;

use ROCrate\DataEntity;

/**
 * Extends the DataEntity class
 */
class File extends DataEntity
{
    /**
     * Constructs a file data entity (more specific data entity)
     * @param string $id The ID of the file data entity
     */
    public function __construct(string $id)
    {
        parent::__construct($id, ['File']);
    }
}
