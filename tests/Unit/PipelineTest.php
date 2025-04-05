<?php

namespace Rosalana\Core\Tests\Unit;

use Rosalana\Core\Facades\Pipeline;
use Rosalana\Core\Tests\TestCase;

class PipelineTest extends TestCase
{
    public function test_basic_pipeline()
    {
        Pipeline::resolve('test')
            ->extend(fn($value, $next) => $next($value . ' world'));

        $result = Pipeline::resolve('test')->run('hello');

        $this->assertIsString($result, 'Expected the result to be a string.');
        $this->assertEquals('hello world', $result);
    }

    public function test_pipeline_chaining()
    {
        $called = [];
        $result = Pipeline::resolve('test')
            ->extend(function ($value, $next) use (&$called) {
                $called[] = 'logger';
                logger($value);
                return $next($value . ' world');
            })
            ->extend(fn($value, $next) => $next($value . '!'))
            ->extend(function ($value, $next) use (&$called) {
                $called[] = 'add';
                return $next($value . '!!!');
            })
            ->run('hello');

        $this->assertIsString($result, 'Expected the result to be a string.');
        $this->assertEquals(['logger', 'add'], $called);
        $this->assertEquals('hello world!!!!', $result);
    }
}
