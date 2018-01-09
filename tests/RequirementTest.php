<?php

namespace LowerSpeck;

use TestCase;
use Mockery;

class RequirementTest extends TestCase
{

    public function testInstantiate()
    {
        $requirement = new Requirement('  1. Description');
        $this->assertEquals('1.', $requirement->id);
        $this->assertEquals([], $requirement->flags);
        $this->assertEquals('Description', $requirement->description);
        $this->asserTequals('  1. Description', $requirement->line);

        $requirement = new Requirement('  2.B.c.  (I,X,UNKNOWN)   DESC   ');
        $this->assertEquals('2.B.c.', $requirement->id);
        $this->assertEquals(['I', 'X', 'UNKNOWN'], $requirement->flags);
        $this->assertEquals('DESC', $requirement->description);
        $this->asserTequals('  2.B.c.  (I,X,UNKNOWN)   DESC   ', $requirement->line);
    }

    public function testHasFlag()
    {
        $requirement = new Requirement('2.a.a. (A,B) System MUST NOT catch fire.');
        $this->assertTrue($requirement->hasFlag('A'));
        $this->assertTrue($requirement->hasFlag('B'));
        $this->assertFalse($requirement->hasFlag('C'));
    }

    /**
     * @LWR 1.g.g.a. The command must output a warning with any requirements 
     * that do not use the keywords defined in RFC 2119.
     */
    public function testHasRfc2119Keywords()
    {
        $requirement = new Requirement('1. Should never');
        $this->assertTrue($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. May never');
        $this->assertTrue($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. Must never');
        $this->assertTrue($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. Should not never');
        $this->assertTrue($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. May not never');
        $this->assertTrue($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. Must not never');
        $this->assertTrue($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. Mostly not');
        $this->assertFalse($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. X');
        $this->assertFalse($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. shoulders');
        $this->assertFalse($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. maybelline');
        $this->assertFalse($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. mustang sally');
        $this->assertFalse($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. Required mackerel');
        $this->assertTrue($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. Shall never');
        $this->assertTrue($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. Recommended soup');
        $this->assertTrue($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. Optional');
        $this->assertTrue($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. xRequired mackerel');
        $this->assertFalse($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. Shallot soup');
        $this->assertFalse($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. xRecommended soup');
        $this->assertFalse($requirement->hasRfc2119Keywords());

        $requirement = new Requirement('1. crOptional');
        $this->assertFalse($requirement->hasRfc2119Keywords());

    }

    /**
     * @LWR 1.g.g.b. The command must output a warning with any requirements 
     * that use custom flags that do not begin with a dash (-).
     */
    public function testGetBadFlags()
    {
        $requirement = new Requirement('1. (I,X,-OK,BAD) Must love lapdogs');
        $this->assertEquals(['BAD'], $requirement->getBadFlags());

        $requirement = new Requirement('1. Must love lapdogs (NOT A FLAG)');
        $this->assertEquals([], $requirement->getBadFlags());
    }

    /**
     * @LWR 1.g.g.d. The command must output an error with any requirements 
     * that cannot be parsed.
     */
    public function testHasParseError()
    {
        $requirement = new Requirement('1. Must be fine');
        $this->assertFalse($requirement->hasParseError());

        $requirement = new Requirement('Must not be fine');
        $this->assertTrue($requirement->hasParseError());

        $requirement = new Requirement('1.a Must not be fine');
        $this->assertTrue($requirement->hasParseError());
    }

    /**
     * follows() should accept an id and tell whether this requirement's id 
     * is appropriate to follow it.
     * 
     * @LWR 1.g.g.e. The command must output an error with any requirement 
     * immediately following a gap.
     *
     * @LWR 1.g.g.g. The command must output an error with any requirement 
     * that is out of order.
     */
    public function testFollows()
    {
        $requirement = new Requirement('2. Must follow');
        $this->assertTrue($requirement->follows('1.'));
        $this->assertTrue($requirement->follows('1.a.'));
        $this->assertTrue($requirement->follows('1.z.z.'));
        $this->assertFalse($requirement->follows(''));
        $this->assertFalse($requirement->follows('2.')); // itself
        $this->assertFalse($requirement->follows('2.a.'));
        $this->assertFalse($requirement->follows('3.'));
        $this->assertFalse($requirement->follows(str_random(8)));

        $requirement = new Requirement('1. Must follow');
        $this->assertTrue($requirement->follows(''));
        $this->assertFalse($requirement->follows('1.')); // itself
        $this->assertFalse($requirement->follows('1.a.'));
        $this->assertFalse($requirement->follows('2.'));
        $this->assertFalse($requirement->follows(str_random(8)));

        $requirement = new Requirement('3.c. Must follow');
        $this->assertTrue($requirement->follows('3.b.'));
        $this->assertTrue($requirement->follows('3.b.a.'));
        $this->assertTrue($requirement->follows('3.b.z.z.'));
        $this->assertFalse($requirement->follows('3.a.'));
        $this->assertFalse($requirement->follows('3.'));
        $this->assertFalse($requirement->follows('2.'));
        $this->assertFalse($requirement->follows('2.z.'));
        $this->assertFalse($requirement->follows(''));
        $this->assertFalse($requirement->follows('3.c.')); // itself
        $this->assertFalse($requirement->follows(''));
        $this->assertFalse($requirement->follows(str_random(8)));

        $requirement = new Requirement('3.c.aa. Must follow');
        $this->assertTrue($requirement->follows('3.c.z.'));
        $this->assertFalse($requirement->follows('3.c.zz.'));
        $this->assertFalse($requirement->follows('3.c.'));
        $this->assertFalse($requirement->follows(''));
        $this->assertFalse($requirement->follows(str_random(8)));

        $requirement = new Requirement('10. Must follow');
        $this->assertTrue($requirement->follows('9.'));
        $this->assertFalse($requirement->follows(''));
        $this->assertFalse($requirement->follows(str_random(8)));
    }
}
