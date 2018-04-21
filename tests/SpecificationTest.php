<?php

namespace LowerSpeck;

use TestCase;
use Mockery;

class SpecificationTest extends TestCase
{

    public function testInstantiate()
    {
        new Specification([]);
    }

    /**
     * Only get the requirements. In order.
     * 
     * @LWR 1.d. The command must parse the `requirements.lwr` file into an 
     * appropriate structure.
     */
    public function testGetRequirements()
    {
        $set = [
            new Requirement('1. Code MUST yield manatees'),
            '',
            new Requirement('2. Hot Cube'),
        ];

        $specification = new Specification($set);

        $requirements = $specification->getRequirements();

        $this->assertCount(2, $requirements);
        $this->assertEquals($set[0], $requirements[0]);
        $this->assertEquals($set[2], $requirements[1]);
    }

    /**
     * Get everything, in order.
     */
    public function testGetAll()
    {
        $set = [
            new Requirement('1. Code MUST yield manatees'),
            '',
            new Requirement('2. Hot Cube'),
        ];

        $specification = new Specification($set);

        $all = $specification->getAll();

        $this->assertCount(3, $all);
        $this->assertEquals($set, $all);
    }

    /**
     * @LWR 1.f.a. If an ID was supplied as an argument, the command MAY only 
     * search for that requirement and its sub-requirements.
     */
    public function testGetByIdWithChildren_ExcludeParseErrors()
    {
        $set = [
            new Requirement('1. Code MUST yield manatees'),
            new Requirement('1.a. Code MUST yield tees'),
            '',
            new Requirement('1.b. Code MUST yield teas'),
            new Requirement('1.b.a. Code MUST yield manna teas'),
            '',
            new Requirement('2. Hot Cube'),
            new Requirement('2.a. Not Cube'),
            new Requirement('2.b Parse error'),
        ];

        $specification = new Specification($set);

        $tree = $specification->getByIdWithChildren(1);

        $this->assertCount(5, $tree);
        $this->assertEquals(array_slice($set, 0, 5), $tree);
    }

    /**
     * @LWR 1.f.a. If an ID was supplied as an argument, the command MAY only 
     * search for that requirement and its sub-requirements.
     *
     * @LWR 1.g.g.d.a. The command MUST output parse errors even for 
     * requirements that do not fall within the super ID supplied to the 
     * command.
     */
    public function testGetByIdWithChildren_IncludeParseErrors()
    {
        $set = [
            new Requirement('1. Code MUST yield manatees'),
            new Requirement('1.a. Code MUST yield tees'),
            '',
            new Requirement('1.b. Code MUST yield teas'),
            new Requirement('1.b.a. Code MUST yield manna teas'),
            '',
            new Requirement('2. Hot Cube'),
            new Requirement('2.a. Not Cube'),
            new Requirement('2.b PARSE ERROR!!!'),
        ];

        $specification = new Specification($set);

        $tree = $specification->getByIdWithChildren(1, true);

        $this->assertCount(6, $tree);

        $match = array_merge(
            array_slice($set, 0, 5),
            [$set[8]]
        );
        $this->assertEquals($match, $tree);
    }

    /**
     * @LWR 1.g.g.f. The command must output an error with any requirement 
     * with an ID that is duplicated.
     */
    public function testIdIsDuplicate()
    {
        $set = [
            new Requirement('1. Code MUST yield manatees'),
            '',
            new Requirement('1. Hot Cube'),
            new Requirement('2. Cold Cube'),
            new Requirement('2.a. Hot Sphere'),
            new Requirement('2.A. Cold Sphere'),
        ];

        $specification = new Specification($set);

        $this->assertTrue($specification->idIsDuplicate('1.'));
        $this->assertFalse($specification->idIsDuplicate('2.'));
        $this->assertFalse($specification->idIsDuplicate('3.'));
        $this->assertTrue($specification->idIsDuplicate('2.a.'));
    }
}
