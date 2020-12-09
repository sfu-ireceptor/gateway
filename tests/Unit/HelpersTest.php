<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    /** @test */
    public function data_set_object()
    {
    	$obj = new \stdClass();
    	data_set_object($obj, 'foo', 'bar');
    	$this->assertSame($obj->foo, 'bar');
    }
}
