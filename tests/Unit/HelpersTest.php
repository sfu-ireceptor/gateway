<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    // copied from Laravel test for data_set()

    /** @test */
    public function dataSetObject()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            ['foo' => 'bar', 'baz' => 'boom'],
            data_set_object($data, 'baz', 'boom')
        );

        $this->assertEquals(
            ['foo' => 'bar', 'baz' => 'kaboom'],
            data_set_object($data, 'baz', 'kaboom')
        );

        $this->assertEquals(
            ['foo' => [], 'baz' => 'kaboom'],
            data_set_object($data, 'foo.*', 'noop')
        );

        $this->assertEquals(
            ['foo' => ['bar' => 'boom'], 'baz' => 'kaboom'],
            data_set_object($data, 'foo.bar', 'boom')
        );
    }

    /** @test */
    public function dataSetObjectWithObject()
    {
        // setting an object property
        $obj = new \stdClass();
        data_set_object($obj, 'foo', 'bar');
        $this->assertSame($obj->foo, 'bar');

        // setting a nested object property
        $obj = new \stdClass();
        $obj->foo = new \stdClass();
        data_set_object($obj, 'foo.bar', 'baz');
        $this->assertSame($obj->foo->bar, 'baz');
    }

    /** @test */
    public function dataSetObjectWithArray()
    {
        // setting an array property
        $arr = [];
        data_set_object($arr, 'foo', 'bar');
        $this->assertSame($arr['foo'], 'bar');

        // setting a nested array property
        $obj = new \stdClass();
        data_set_object($obj, 'foo.0.bar', 'baz');
        $this->assertSame($obj->foo[0]->bar, 'baz');
    }
}
