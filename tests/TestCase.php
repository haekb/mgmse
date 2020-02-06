<?php

namespace Tests;

use Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use ReflectionMethod;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @param $class
     * @param $function
     * @return ReflectionMethod|null
     */
    public function accessProtected($class, $function): ?ReflectionMethod
    {
        try {
            $reflection = new ReflectionMethod($class, $function);
        } catch (\ReflectionException $e) {
            return null;
        }

        // Let us access it!
        $reflection->setAccessible(true);

        return $reflection;
    }
}
