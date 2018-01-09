<?php

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class TestCase extends Orchestra\Testbench\TestCase
{

    protected $lastArtisanOutput;

    public function registerArtisanCommand($command)
    {
        $this->app[Kernel::class]->registerCommand($command);
    }

    public function artisan($command, $parameters = [])
    {   
        $kernel = $this->app[Kernel::class];

        $kernel->call($command, $parameters);

        $this->lastArtisanOutput = $kernel->output();
    }

    public function getArtisanOutput()
    {
        return $this->lastArtisanOutput;
    }

    public function seeInArtisan($substr)
    {
        $this->assertRegexp('/' . $substr . '/', $this->getArtisanOutput());
    }
}
