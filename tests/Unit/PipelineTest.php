<?php

namespace Rosalana\Core\Tests\Unit;

use Rosalana\Core\Facades\Pipeline;
use Rosalana\Core\Tests\TestCase;

class PipelineTest extends TestCase
{
    public function test_basic_pipeline()
    {
        Pipeline::resolve('test')
            ->extend(fn($value) => $value . ' world');

        $result = Pipeline::resolve('test')->run('hello');

        $this->assertIsString($result, 'Expected the result to be a string.');
        $this->assertEquals('hello world', $result);
    }

    public function test_pipline_chain_with_two_arguments()
    {
        $result = Pipeline::resolve('two')
            ->extend(function ($value, $next) {
                $value . ' world';
                return $next($value);
            })
            ->extend(fn($value) => strtoupper($value))
            ->run('hello');

        $this->assertIsString($result, 'Expected the result to be a string.');
        $this->assertEquals('HELLO', $result);
    }

    public function test_pipline_chain_with_one_arguments_no_return()
    {
        $result = Pipeline::resolve('two')
            ->extend(function ($value, $next) {
                $value . ' world';
                return $next($value);
            })
            ->extend(fn($value) => strtoupper($value))
            ->run('hello');

        $this->assertIsString($result, 'Expected the result to be a string.');
        $this->assertEquals('HELLO', $result);
    }

    public function test_pipline_chain_with_one_arguments()
    {
        $result = Pipeline::resolve('one')
            ->extend(function ($value) {
                return $value . ' world';
            })
            ->extend(fn($value) => strtoupper($value))
            ->run('hello');

        $this->assertIsString($result, 'Expected the result to be a string.');
        $this->assertEquals('HELLO WORLD', $result);
    }

    public function test_pipline_chain_with_none_arguments()
    {
        $called = false;

        $result = Pipeline::resolve('none')
            ->extend(function () use (&$called) {
                $called = true;
            })
            ->extend(fn($value) => strtoupper($value))
            ->run('hello');

        $this->assertTrue($called, 'Expected the pipe with no arguments to be called.');
        $this->assertIsString($result, 'Expected the result to be a string.');
        $this->assertEquals('HELLO', $result, 'Pipe with no arguments should not stop the chain.');
    }
}
