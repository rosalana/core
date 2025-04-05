<?php

namespace Rosalana\Core\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Rosalana\Core\Facades\Pipeline;
use Rosalana\Core\Providers\RosalanaCoreServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            RosalanaCoreServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Pipeline::flush();
    }
}
