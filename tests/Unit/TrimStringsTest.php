<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;
use App\Http\Middleware\TrimStrings;

class TrimStringsTest extends TestCase
{
    /** @test */	
    public function trimSimpleFields()
    {
        $middleware = new TrimStrings;
        $request = new Request(
            [
            	'field1' => 'value1',
            	'field2' => 'value2 ',
            	'field3' => ' value3',
            	'field4' => ' value4 ',
            	'field5' => '  value5  '
            ]
        );

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('value1', $request->get('field1'));
            $this->assertEquals('value2', $request->get('field2'));
            $this->assertEquals('value3', $request->get('field3'));
            $this->assertEquals('value4', $request->get('field4'));
            $this->assertEquals('value5', $request->get('field5'));
        });
    }

    /** @test */	
    public function trimArrayFields()
    {
        $middleware = new TrimStrings;
        $request = new Request(
            [
            	'field1' => ['value1', 'value2'],
            	
            	'field2' => ['value1', 'value2 '],
            	'field3' => ['value1', ' value2'],
            	'field4' => ['value1', ' value2 '],
	           	'field5' => ['value1', '  value2  '],

            	'field6' => ['value1 ', 'value2'],
            	'field7' => [' value1', 'value2'],
            	'field8' => [' value1 ', 'value2'],
            	'field9' => ['  value1  ', 'value2'],

            	'field10' => ['  value1  ', '  value2  '],
            ]
        );

        $middleware->handle($request, function (Request $request) {
        	for ($i=1; $i <=10 ; $i++) { 
        		$t = $request->get('field' . $i);
	            $this->assertEquals('value1', $t[0]);
	            $this->assertEquals('value2', $t[1]);        	
        	}
        });
    }

    /** @test */	
    public function trimSimpleFieldsExceptSome()
    {
        $middleware = new TrimStrings2;

        $request = new Request(
            [
            	'field1' => 'value1',
            	'field2' => 'value2 ',
            	'field3' => ' value3',
            	'field4' => ' value4 ',
            	'field5' => '  value5  '
            ]
        );

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('value1', $request->get('field1'));
            $this->assertEquals('value2', $request->get('field2'));
            $this->assertEquals(' value3', $request->get('field3'));
            $this->assertEquals(' value4 ', $request->get('field4'));
            $this->assertEquals('value5', $request->get('field5'));
        });
    }

    /** @test */	
    public function trimArrayFieldsExceptSome()
    {
        $middleware = new TrimStrings2;
        $request = new Request(
            [
            	'field1' => ['value1', 'value2'],
            	
            	'field2' => ['value1', 'value2 '],
            	'field3' => ['value1', ' value2'],
            	'field4' => ['value1', ' value2 '],
	           	'field5' => ['value1', '  value2  '],

            	'field6' => ['value1 ', 'value2'],
            	'field7' => [' value1', 'value2'],
            	'field8' => [' value1 ', 'value2'],
            	'field9' => ['  value1  ', 'value2'],

            	'field10' => ['  value1  ', '  value2  '],
            ]
        );

        $middleware->handle($request, function (Request $request) {
        	for ($i=1; $i <=10 ; $i++) { 
        		$t = $request->get('field' . $i);
        		if($i == 3) {
		            $this->assertEquals('value1', $t[0]);
		            $this->assertEquals(' value2', $t[1]);        	        			
        		}
        		else if ($i == 4) {
		            $this->assertEquals('value1', $t[0]);
		            $this->assertEquals(' value2 ', $t[1]);        	        			        			
        		}
        		else {
		            $this->assertEquals('value1', $t[0]);
		            $this->assertEquals('value2', $t[1]);        			
        		}
        	}
        });
    }
}

class TrimStrings2 extends TrimStrings
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array
     */
    protected $except = [
        'field3',
        'field4',
    ];
}