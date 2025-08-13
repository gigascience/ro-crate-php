<?php

declare(strict_types=1);

namespace Tests;

// tests/JsonFlattenerTest.php
use PHPUnit\Framework\TestCase;
use Json\Flattener;

class JsonFlattenerTest extends TestCase
{
    private Flattener $flattener;

    protected function setUp(): void
    {
        $this->flattener = new Flattener();
    }

    public function testFlattenSimpleArray(): void
    {
        $input = ['a' => 1, 'b' => 2];
        $expected = ['a' => 1, 'b' => 2];
        $this->assertEquals($expected, $this->flattener->flatten($input));
    }

    public function testFlattenNestedArray(): void
    {
        $input = [
            'a' => 1,
            'b' => ['c' => 2, 'd' => ['e' => 3]]
        ];

        $expected = [
            'a' => 1,
            'b.c' => 2,
            'b.d.e' => 3
        ];

        $this->assertEquals($expected, $this->flattener->flatten($input));
    }

    public function testCustomSeparator(): void
    {
        $input = ['a' => ['b' => 1]];
        $expected = ['a_b' => 1];
        $this->assertEquals($expected, $this->flattener->flatten($input, '', '_'));
    }
}
