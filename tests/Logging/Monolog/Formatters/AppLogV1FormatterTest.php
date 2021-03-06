<?php

declare(strict_types=1);

namespace NB\AppComponents\Test\Logging\Monolog\Formatters;

use PHPUnit\Framework\TestCase;
use NB\AppComponents\Logging\Monolog\Formatters\AppLogV1Formatter;

class AppLogV1FormatterTest extends TestCase
{
    private string $service = 'test.server';
    private string $env = 'prod';

    public function testValidInfo()
    {
        $formatter = new AppLogV1Formatter($this->service, $this->env);

        $now = new \DateTime();
        $record = [
            'message' => 'test',
            'context' => [],
            'level' => 200,
            'level_name' => 'INFO',    // level_name错误
            'channel' => 'test',
            'datetime' => $now,
            'extra' => [],
        ];
        $actual = $formatter->format($record);
        $expect = [
            'schema' => AppLogV1Formatter::SCHEMA,
            't' => $now->format('Y-m-d\TH:i:s.uP'),
            'l' => 'info',
            's' => $this->service,
            'c' => 'test',
            'e' => $this->env,
            'm' => $record['message'],
            'ctx' => new class{},
        ];
        $this->assertEquals(json_encode($expect)."\n", $actual);

        // 测试record['extra']中的信息增加到context中,并且不应该覆盖同名字段
        $now = new \DateTime();
        $record = [
            'message' => 'test',
            'context' => [
                'hi' => 'yes',
                'info' => 123,      // info 不会被extra中的同名字段覆盖
            ],
            'level' => 200,
            'level_name' => 'INFO',    // level_name错误
            'channel' => 'test',
            'datetime' => $now,
            'extra' => [
                'abc' => 'def',
                'info' => [
                    'test' => 'test1',
                ],
            ],
        ];
        $actual = $formatter->format($record);
        $expect = [
            'schema' => AppLogV1Formatter::SCHEMA,
            't' => $now->format('Y-m-d\TH:i:s.uP'),
            'l' => 'info',
            's' => $this->service,
            'c' => 'test',
            'e' => $this->env,
            'm' => $record['message'],
            'ctx' => [
                'abc' => 'def',
                'info' => 123,
                'hi' => 'yes',
            ],
        ];
        $this->assertEquals(json_encode($expect)."\n", $actual);
    }

    /**
     * 测试level_name为一个无效值所生成的log
     */
    public function testInvalidInfo_WithInvalidLevelName()
    {
        $formatter = new AppLogV1Formatter($this->service, $this->env);

        $now = new \DateTime();
        $record = [
            'message' => 'test',
            'context' => [],
            'level' => 200,
            'level_name' => 'ASDASDSAD',    // level_name错误
            'channel' => 'test',
            'datetime' => $now,
            'extra' => [],
        ];
        $actual = $formatter->format($record);
        $expect = [
            'schema' => AppLogV1Formatter::SCHEMA,
            't' => $now->format('Y-m-d\TH:i:s.uP'),
            'l' => 'warning',
            's' => $this->service,
            'c' => 'test',
            'e' => $this->env,
            'm' => $record['message'],
            'ctx' => [
                'ctxErr' => [
                    [
                        'errMsg' => 'undefined level name',
                        'levelName' => 'ASDASDSAD',
                    ],
                ],
            ],
        ];
        $this->assertEquals(json_encode($expect)."\n", $actual);
    }

    /**
     * 测试context字段为一个非法类型所生成的log
     */
    public function testInvalidInfo_WithInvalidContext()
    {
        $formatter = new AppLogV1Formatter($this->service, $this->env);

        $now = new \DateTime();
        $record = [
            'message' => 'test',
            'context' => 'invalid_context',   // 提供不合法的context类型
            'level' => 200,
            'level_name' => 'INFO',
            'channel' => 'test',
            'datetime' => $now,
            'extra' => [],
        ];
        $actual = $formatter->format($record);
        $expect = [
            'schema' => AppLogV1Formatter::SCHEMA,
            't' => $now->format('Y-m-d\TH:i:s.uP'),
            'l' => 'info',
            's' => $this->service,
            'c' => 'test',
            'e' => $this->env,
            'm' => $record['message'],
            'ctx' => [
                'ctxErr' => [
                    [
                        'errMsg' => 'invalid context type in log data',
                        'type' => 'string',
                    ],
                ],
            ],
        ];
        $this->assertEquals(json_encode($expect)."\n", $actual);

        // 传递索引数组会认为不正确
        $record['context'] = ['abc'];
        $actual = $formatter->format($record);
        $expect['ctx'] = [
            0 => 'abc',
            'ctxErr' => [
                [
                    'errMsg' => 'context should be an assoc array',
                ],
            ],
        ];
        $this->assertEquals(json_encode($expect)."\n", $actual);
    }
}