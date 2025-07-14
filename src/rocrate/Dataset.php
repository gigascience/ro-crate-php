<?php

namespace ROCrate;

use ROCrate\Entity;

/**
 * Extends the Entity class
 */
class Dataset extends Entity {
    /**
     * Constructs a dataset entity, i.e. root data entity
     * @param string $id The ID of the dataset entity
     */
    public function __construct(string $id = './') {
        parent::__construct($id, ['Dataset']);
    }

    /**
     * Gets the information of the dataset entity as an array for printing, debugging and inheritance
     * @return array The information array
     */
    public function toArray(): array {
        $data = parent::baseArray();
        //$data['datePublished'] = date('c'); // !!!
        return array_merge($data, $this->properties);
    }
}