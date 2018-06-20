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

        $expected_data = [
            new Requirement('1. Something MUST do some action.'),
            '',
            new Requirement('1.a. (X, I) Something MAY do some other action.'),
            '',
        ];
        
        $specification = $parser->getSpecification();
        
        $this->assertEquals($expected_data, $specification->getAll());
    }

}
