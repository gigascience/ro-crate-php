<?php

namespace ROCrate;

/**
 * Handles the ro-crate metadata descriptor and other data entities of normal type, root type and contextual type
 */
class DataEntity {
    private int $index = 0;
    // need handle id type value specially ? or only when formatting the file when read and write
    // get set
    // list of tuples
    // add doc
    private Tuple $pair = NULL;
    public function __construct(int $index, ) 
    {
        $this->index = $index;
    }
    public function __destruct() {}


    
    public function __toString(): string  {return "Internal Index: " . $this->index;}
}