<?php

namespace LowerSpeck;

use TestCase;
use Mockery;

class ConfigTest extends TestCase
{

    public function testInstantiate()
    {
        new Config(str_random(9));
    }

    /**
     * @LWR 1.b.a. The command should expect the config file to contain an 
     * object in JSON format.
     * 
     * @LWR 1.b.b. The command should expect the object's key named `paths` to 
     * have an array of strings.
     * 
     * @LWR 1.c. The command must use default values if the `lower-speck.json` 
     * file is absent.
     */
    public function testPaths()
    {
        if (file_exists(base_path('x.json'))) {
            unlink(base_path('x.json'));
        }
        $config = new Config(base_path('x.json'));
        $this->assertEquals(['.'], $config->paths());

        file_put_contents(base_path('x.json'), '');
        $config = new Config(base_path('x.json'));
        $this->assertEquals(['.'], $config->paths());

        file_put_contents(base_path('x.json'), json_encode([]));
        $config = new Config(base_path('x.json'));
        $this->assertEquals(['.'], $config->paths());

        file_put_contents(base_path('x.json'), json_encode([
            'paths' => [],
        ]));
        $config = new Config(base_path('x.json'));
        $this->assertEquals(['.'], $config->paths());

        $path = str_random(8);
        file_put_contents(base_path('x.json'), json_encode([
            'paths' => [$path],
        ]));
        $config = new Config(base_path('x.json'));
        $this->assertEquals([$path], $config->paths());

        $paths = [str_random(8), str_random(8)];
        file_put_contents(base_path('x.json'), json_encode([
            'paths' => $paths,
        ]));
        $config = new Config(base_path('x.json'));
        $this->assertEquals($paths, $config->paths());
    }
}
