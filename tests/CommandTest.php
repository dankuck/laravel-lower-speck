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
     * @LWR 1.a. The command must expect the Lower Speck file to be named 
     * `requirements.lwr` and reside at the root of the project.
     * 
     * @LWR 1.b. The command should expect a file named `lower-speck.json` to 
     * reside at the root of the project.
     * 
     * @LWR 1.d. The command must parse the `requirements.lwr` file into an 
     * appropriate structure.
     * 
     * @LWR 1.e.a. The command must accept `-v` as an argument to enter 
     * verbose mode.
     *
     * @LWR 1.e.b. The command must accept `-vv` as an argument to enter 
     * double-verbose mode.
     *
     * @LWR 1.f. The command must grep the directories from the `paths` array 
     * (or else the root) recursively to find strings referencing the 
     * requirements from the `requirements.lwr` file.
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
     *
     * @LWR 1.e.c. The command must accept an ID as an optional argument.
     */
    public function testHandle()
    {
        $command = $this->app->make(Command::class);
        $this->registerArtisanCommand($command);

        $caught_requirements_file = null;
        $caught_config_file = null;
        $caught_paths_list = null;
        $caught_specification = null;
        $caught_grepper = null;
        $caught_command = null;
        $caught_analysis = null;
        $caught_verbosity = null;
        $caught_id = null;

        $id = 2;
        $spec = Mockery::mock(Specification::class);
        $grepper = Mockery::mock(ReferenceGrepper::class);
        $analysis = Mockery::mock(Analysis::class);

        $this->app->bind(Parser::class, function ($app, $args) use (&$caught_requirements_file, $spec) {
            $caught_requirements_file = $args[0];
            $parser = Mockery::mock(Parser::class);
            $parser->shouldReceive('getSpecification')
                ->once()
                ->andReturn($spec);
            return $parser;
        });
        
        $this->app->bind(Config::class, function ($app, $args) use (&$caught_config_file) {
            $caught_config_file = $args[0];
            $config = Mockery::mock(Config::class);
            $config->shouldReceive('paths')
                ->andReturn(['x', 'y', 'z']);
            return $config;
        });
        
        $this->app->bind(ReferenceGrepper::class, function ($app, $args) use (&$caught_paths_list, $grepper) {
            $caught_paths_list = $args[0];
            return $grepper;
        });

        $this->app->bind(Analyzer::class, function ($app, $args) use (&$caught_specification, &$caught_grepper, $analysis, $id) {
            $caught_specification = $args[0];
            $caught_grepper = $args[1];
            $analyzer = Mockery::mock(Analyzer::class);
            $analyzer->shouldReceive('getAnalysis')
                ->once()
                ->with("{$id}.")
                ->andReturn($analysis);
            return $analyzer;
        });

        $this->app->bind(Reporter::class, function ($app, $args) use (&$caught_command, &$caught_analysis, &$caught_verbosity, &$caught_id) {
            $caught_analysis = $args[0];
            $caught_verbosity = $args[1];
            $caught_id = $args[2];
            $reporter = Mockery::mock(Reporter::class);
            $reporter->shouldReceive('report')
                ->with(Mockery::on(function ($arg) use (&$caught_command) {
                    $caught_command = $arg;
                    return true;
                }));
            return $reporter;
        });

        $this->artisan('requirements:check', ['-v' => true, 'id' => $id]);

        $this->assertEquals(base_path('requirements.lwr'), $caught_requirements_file);
        $this->assertEquals(base_path('lower-speck.json'), $caught_config_file);
        $this->assertEquals([base_path('x'), base_path('y'), base_path('z')], $caught_paths_list);
        $this->assertEquals($spec, $caught_specification);
        $this->assertEquals($grepper, $caught_grepper);
        $this->assertTrue($caught_command instanceof Command);
        $this->assertEquals($analysis, $caught_analysis);
        $this->assertEquals(1, $caught_verbosity);
        // strcmp is used next because == treats 2. as equal to 2
        $this->assertEquals(0, strcmp("{$id}.", $caught_id)); 

        $this->seeInArtisan('Loading config...');
        $this->seeInArtisan('Parsing requirements.lwr...');
        $this->seeInArtisan('Grepping code base...');
        $this->seeInArtisan('Processing...');
        $this->seeInArtisan('Results:');
    }
}
