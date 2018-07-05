<?php

namespace LowerSpeck;

use TestCase;
use Mockery;

class CommandTest extends TestCase
{

    /**
     * @LWR 1. This package must provide an artisan command to parse a Lower 
     * Speck file and output information about it.
     */
    public function testExists()
    {
        $this->app->make(Command::class); // no whammy
    }

    /**
     * @LWR 1. This package must provide an artisan command to parse a Lower 
     * Speck file and output information about it.
     */
    public function testRegister()
    {
        $command = $this->app->make(Command::class);

        $this->registerArtisanCommand($command);

        $this->artisan('list');

        $this->seeInArtisan('requirements:check');
    }

    /**
     * @LWR 1.e.c. The command MUST accept an ID as an optional argument.
     * 
     * @LWR 1.g.a. If an ID was supplied as an argument, the command MUST only 
     * give output relative to that requirement and its sub-requirements.
     * 
     * @LWR 1.e.a. The command must accept `-v` as an argument to enter 
     * verbose mode.
     *
     * @LWR 1.e.b. The command must accept `-vv` as an argument to enter 
     * double-verbose mode.
     * 
     * @LWR 1.g. The command must output a description of the code's 
     * references to the requirements.
     *
     * @LWR 1.g.f.a In normal mode and above the command must output any 
     * requirements that are not addressed and not obsolete as well as any 
     * incomplete requirements that are addressed and not obsolete.
     * 
     * @LWR 1.g.f.b. In verbose mode and above the command must output all 
     * requirements that are not obsolete.
     * 
     * @LWR 1.g.f.c. In double-verbose mode and above the command must output 
     * all requirements.
     */
    public function testHandle()
    {
        $command = $this->app->make(Command::class);
        $this->registerArtisanCommand($command);
        $id = 2;
        $analysis = Mockery::mock(Analysis::class);
       
        $this->app->bind(Checker::class, function ($app, $args) use (&$caught_base_path, $analysis) {
            $caught_base_path = $args['base_path'];
            $checker = Mockery::mock(Checker::class);
            $checker->shouldReceive('check')
                ->once()
                ->with(2)
                ->andReturn($analysis);
            return $checker;
        });

        $this->app->bind(Reporter::class, function ($app, $args) use (&$caught_command, &$caught_analysis, &$caught_verbosity) {
            $caught_analysis = $args['analysis'];
            $caught_verbosity = $args['verbosity'];
            $reporter = Mockery::mock(Reporter::class);
            $reporter->shouldReceive('report')
                ->with(Mockery::on(function ($arg) use (&$caught_command) {
                    $caught_command = $arg;
                    return true;
                }));
            return $reporter;
        });

        $this->artisan('requirements:check', ['-v' => true, 'id' => $id]);

        $this->assertEquals(base_path(), $caught_base_path);
        $this->assertTrue($caught_command instanceof Command);
        $this->assertEquals($analysis, $caught_analysis);
        $this->assertEquals(1, $caught_verbosity);
    }
}
