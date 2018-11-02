<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\Request;
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
                'field5' => '  value5  ',
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
            for ($i = 1; $i <= 10; $i++) {
                $t = $request->get('field' . $i);
                $this->assertEquals('value1', $t[0]);
                $this->assertEquals('value2', $t[1]);
            }
        });
    }

    /** @test */
    public function trimSimpleFieldsExceptSome()
    {
        $middleware = new TrimStringsExcept;

        $request = new Request(
            [
                'field1' => 'value1',
                'field2' => 'value2 ',
                'field3' => ' value3',
                'field4' => ' value4 ',
                'field5' => '  value5  ',
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
        $middleware = new TrimStringsExcept;
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
            for ($i = 1; $i <= 10; $i++) {
                $t = $request->get('field' . $i);
                if ($i == 3) {
                    $this->assertEquals('value1', $t[0]);
                    $this->assertEquals(' value2', $t[1]);
                } elseif ($i == 4) {
                    $this->assertEquals('value1', $t[0]);
                    $this->assertEquals(' value2 ', $t[1]);
                } else {
                    $this->assertEquals('value1', $t[0]);
                    $this->assertEquals('value2', $t[1]);
                }
            }
        });
    }

    /** @test */
    public function trimArrayFieldsExceptSome2()
    {
        $middleware = new TrimStringsExcept;
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
                'nested' => [
                                [
                                    'field1' => ' trimmed ',
                                    'field2' => ' not trimmed ',
                                ],
                            ],
            ]
        );

        $middleware->handle($request, function (Request $request) {
            for ($i = 1; $i <= 10; $i++) {
                $t = $request->get('field' . $i);
                if ($i == 3) {
                    $this->assertEquals('value1', $t[0]);
                    $this->assertEquals(' value2', $t[1]);
                } elseif ($i == 4) {
                    $this->assertEquals('value1', $t[0]);
                    $this->assertEquals(' value2 ', $t[1]);
                } else {
                    $this->assertEquals('value1', $t[0]);
                    $this->assertEquals('value2', $t[1]);
                }
            }
        });
    }

    /** @test */
    public function testTrimNestedArrayFieldsExceptSome()
    {
        $middleware = new TrimStringsExcept;
        $request = new Request(
            [
                'nested' => [
                                [
                                    'field1' => ' trimmed ',
                                    'field3' => ' not trimmed ',
                                ],
                            ],
            ]
        );

        $middleware->handle($request, function (Request $request) {
            $t = $request->get('nested');
            $this->assertEquals('trimmed', $t[0]['field1']);
            $this->assertEquals(' not trimmed ', $t[0]['field3']);
        });
    }
}

class TrimStringsExcept extends TrimStrings
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
