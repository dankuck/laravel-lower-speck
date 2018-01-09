<?php

namespace LowerSpeck;

use TestCase;
use Mockery;

class ParserTest extends TestCase
{

    public function testInstantiate()
    {
        new Parser('');
    }

    /**
     * @LWR 1.d. The command must parse the `requirements.lwr` file into an 
     * appropriate structure.
     */
    public function testGetSpecification()
    {
        file_put_contents(base_path('x.lwr'),
              "1. Something MUST do some action.\n"
            . "\n"
            . "1.a. (X, I) Something MAY do some other action.\n"
        );

        $parser = new Parser(base_path('x.lwr'));

        $specification = Mockery::mock(Specification::class);

        $this->app->bind(Specification::class, function ($app, $args) use (&$caught_data, $specification) {
            $caught_data = $args[0];
            return $specification;
        });

        $expected_data = [
            new Requirement('1. Something MUST do some action.'),
            '',
            new Requirement('1.a. (X, I) Something MAY do some other action.'),
            '',
        ];

        $this->assertEquals($specification, $parser->getSpecification());
        $this->assertEquals($expected_data, $caught_data);
    }

}
