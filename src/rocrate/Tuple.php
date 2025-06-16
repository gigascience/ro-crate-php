<?php

namespace ROCrate;

class Tuple 
{
    // add doc
    // get set
    private string $key;
    private array $elements;
    public function __construct(string $key, array $elements) {}
    public function __destruct() {}
    public function getKey(): string 
    {
        return $this->key;
    }
    // list of tuples [{}]
    // can we use array with => instead
}