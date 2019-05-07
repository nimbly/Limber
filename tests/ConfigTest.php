<?php

namespace Limber\Tests;

use Limber\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function test_all()
    {
        $config = new Config(
            __DIR__ . "/Mock/config",
            [
                'key1' => 'foo',
                'key2' => 'bar'
            ]
        );

        $this->assertEquals(
            [
                'key1' => 'foo',
                'key2' => 'bar'
            ],
            $config->all()
        );
    }

    public function test_has()
    {
        $config = new Config(
            __DIR__ . "/Mock/config",
            [
                'key1' => 'foo',
                'key2' => 'bar'
            ]
        );

        $this->assertTrue($config->has('key1'));
    }

    public function test_does_not_have()
    {
        $config = new Config(
            __DIR__ . "/Mock/config",
            [
                'key1' => 'foo',
                'key2' => 'bar'
            ]
        );

        $this->assertFalse($config->has('key3'));
    }

    public function test_get()
    {
        $config = new Config(
            __DIR__ . "/Mock/config",
            [
                'key1' => 'foo',
                'key2' => 'bar'
            ]
        );

        $this->assertEquals('foo', $config->get('key1'));
    }

    public function test_add()
    {
        $config = new Config(
            __DIR__ . "/Mock/config",
            [
                'key1' => 'foo'
            ]
        );

        $config->add('key2', 'bar');

        $this->assertEquals('bar', $config->get('key2'));
    }

    public function test_load()
    {
        $config = new Config(
            __DIR__ . "/Mock/config",
            [
                'key1' => 'foo'
            ]
        );

        $config->load('test.key1');

        $this->assertEquals('foo', $config->get('test.key1'));
    }

    public function test_loadfile()
    {
        $config = new Config(
            __DIR__ . "/Mock/config",
            [
                'key1' => 'foo'
            ]
        );

        $config->loadFile('balls', __DIR__ . "/Mock/config/test.php");
        $this->assertEquals('foo', $config->get('balls.key1'));
    }

    public function test_loading_file_that_does_not_exist_throws_exception()
    {
        $config = new Config(
            __DIR__ . "/Mock/config"
        );

        $this->expectException(\Exception::class);
        $config->get('foo.bar');
    }

    public function test_getting_with_default_value()
    {
        $config = new Config(
            __DIR__ . "/Mock/config"
        );

        $this->assertEquals('default', $config->get('test.key5', 'default'));
    }
}