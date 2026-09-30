<?php

declare(strict_types=1);

namespace Amon\ModuleGenerator\Tests;

use Amon\ModuleGenerator\ModuleGeneratorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ModuleGeneratorServiceProvider::class];
    }
}
