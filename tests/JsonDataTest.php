<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\JsonData;

class JsonDataTest extends TestCase
{
    public function testBasicOperations()
    {
        $data = new JsonData();
        $data['name'] = 'Alice';
        $data->age = 30;

        $this->assertEquals('Alice', $data['name']);
        $this->assertEquals(30, $data->age);
        $this->assertTrue(isset($data['name']));
        $this->assertTrue(isset($data->age));
    }

    public function testNestedStructures()
    {
        $data = new JsonData();
        $data->person = [
            'name' => 'Bob',
            'contacts' => [
                'email' => 'bob@example.com'
            ]
        ];

        $this->assertInstanceOf(JsonData::class, $data->person);
        $this->assertInstanceOf(JsonData::class, $data->person->contacts);
        $this->assertEquals('bob@example.com', $data->person->contacts->email);

        // Test modification
        $data->person->contacts->phone = '123-456';
        $this->assertEquals('123-456', $data['person']['contacts']['phone']);
    }

    public function testArrayOperations()
    {
        $data = new JsonData();
        $data[] = 'first';
        $data[] = 'second';

        $this->assertCount(2, $data);
        $this->assertEquals('first', $data[0]);
        $this->assertEquals('second', $data[1]);

        unset($data[0]);
        $this->assertCount(1, $data);
        $this->assertEquals('second', $data[1]);
    }

    public function testToArrayConversion()
    {
        $original = [
            'a' => 1,
            'b' => [2, 3],
            'c' => [
                'd' => new JsonData(['e' => 4])
            ]
        ];

        $data = new JsonData($original);
        $array = $data->toArray();

        $this->assertEquals([
            'a' => 1,
            'b' => [2, 3],
            'c' => [
                'd' => ['e' => 4]
            ]
        ], $array);
    }

    public function testJsonConversion()
    {
        $data = new JsonData([
            'name' => 'Charlie',
            'active' => true,
            'scores' => [88, 92]
        ]);

        $json = $data->toJson();
        $this->assertEquals(
            '{"name":"Charlie","active":true,"scores":[88,92]}',
            $json
        );

        $prettyJson = $data->toJson(JSON_PRETTY_PRINT);
        $this->assertStringContainsString("\n", $prettyJson);

        // Test round-trip conversion
        $newData = JsonData::fromJson($json);
        $this->assertEquals($data->toArray(), $newData->toArray());
    }

    public function testInvalidJsonHandling()
    {
        $flag = true;

        try {
            JsonData::fromJson('{invalid json}');
        } catch (\InvalidArgumentException $e) {
            $flag = false;
            $this->assertEquals('JSON decode error: Syntax error', $e->getMessage());
        }

        //$this->expectException(\InvalidArgumentException::class);
        //$this->expectExceptionMessage('JSON decode error');

        if ($flag) {
            $this->assertEquals(true, false);
        }
    }

    public function testIteration()
    {
        $data = new JsonData(['a' => 1, 'b' => 2, 'c' => 3]);
        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $result);
    }

    public function testCountable()
    {
        $data = new JsonData([1, 2, 3]);
        $this->assertCount(3, $data);

        $data->items = ['a', 'b'];
        $this->assertCount(2, $data->items);
    }

    public function testStringRepresentation()
    {
        $data = new JsonData(['test' => 'value']);
        $this->assertEquals('{"test":"value"}', (string)$data);
    }

    public function testComplexStructure()
    {
        $data = new JsonData();
        $data->users = [
            [
                'id' => 1,
                'roles' => ['admin', 'user']
            ],
            [
                'id' => 2,
                'roles' => ['user']
            ]
        ];

        $this->assertEquals('admin', $data->users[0]->roles[0]);
        $this->assertCount(2, $data->users[0]->roles);
        $this->assertCount(1, $data->users[1]->roles);

        // Modify nested array
        $data->users[1]->roles[] = 'editor';
        $this->assertCount(2, $data->users[1]->roles);
        $this->assertEquals('editor', $data->users[1]->roles[1]);
    }
}
