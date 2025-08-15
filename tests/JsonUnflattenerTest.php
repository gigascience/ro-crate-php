<?php

declare(strict_types=1);

namespace Tests;

// tests/JsonUnflattenerTest.php
use PHPUnit\Framework\TestCase;
use Json\Unflattener;

class JsonUnflattenerTest extends TestCase
{
    private Unflattener $unflattener;

    protected function setUp(): void
    {
        $this->unflattener = new Unflattener();
    }

    public function testUnflattenSimple(): void
    {
        $input = ['a' => 1, 'b' => 2];
        $expected = ['a' => 1, 'b' => 2];
        $this->assertEquals($expected, $this->unflattener->unflatten($input));
    }

    public function testUnflattenNested(): void
    {
        $input = [
            'a' => 1,
            'b.c' => 2,
            'b.d.e' => 3
        ];

        $expected = [
            'a' => 1,
            'b' => [
                'c' => 2,
                'd' => ['e' => 3]
            ]
        ];

        $this->assertEquals($expected, $this->unflattener->unflatten($input));
    }

    public function testCustomSeparator(): void
    {
        $input = ['a_b' => 1];
        $expected = ['a' => ['b' => 1]];
        $this->assertEquals($expected, $this->unflattener->unflatten($input, '_'));
    }
}
