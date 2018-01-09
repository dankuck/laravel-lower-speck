<?php

namespace LowerSpeck;

use TestCase;
use Mockery;

class ReferenceGrepperTest extends TestCase
{

    public function testInstantiate()
    {
        new ReferenceGrepper(['.']);

        new ReferenceGrepper(['a', 'b', 'c']);

        $caught = null;
        try {
            new ReferenceGrepper([]);
        } catch (\Exception $e) {
            $caught = $e;
        }
        $this->assertNotNull($caught);
    }

    /**
     * @LWR 1.f. The command must grep the directories from the `paths` array 
     * (or else the root) recursively to find strings referencing the 
     * requirements from the `requirements.lwr` file.
     */
    public function testhasReferenceTo()
    {
        $path1 = base_path(str_random(4));
        $file1 = $path1 . '/' . str_random(8) . '.php';

        $path2 = base_path(str_random(4));
        $file2 = $path2 . '/' . str_random(8) . '.php';
        $file3 = $path2 . '/' . str_random(8) . '.php';

        $file4 = base_path(str_random(8));

        mkdir($path1);
        mkdir($path2);

        file_put_contents($file1, "blah blah LWR 1.a. blor blor XLWR 1.d.");
        file_put_contents($file2, "@LWR 1.b Crab pepl lwr    \t1.e.");
        file_put_contents($file3, "LWR LWR 1.g. LWR");
        file_put_contents($file4, "lmnop LWR 1.c. Proper");

        $grepper = new ReferenceGrepper([$path1, $path2]);

        try {
            $this->assertTrue($grepper->hasReferenceTo('1.a.'), 'Should find 1.a., it is in a searchable directory and properly formatted');
            $this->assertTrue($grepper->hasReferenceTo('1.b.'), 'Should find 1.b. even though it is missing a final dot');
            $this->assertFalse($grepper->hasReferenceTo('1.c.'), 'Should not find 1.c. It is not in a searchable directory');
            $this->assertFalse($grepper->hasReferenceTo('1.d.'), 'Should not find 1.d. It is not in a proper format');
            $this->assertTrue($grepper->hasReferenceTo('1.e.'), 'Should find 1.e. despite it is lower case');
            $this->assertFalse($grepper->hasReferenceTo('1.f.'), 'Should not find 1.f. It is not present');
            $this->assertTrue($grepper->hasReferenceTo('1.g.'), 'Should find 1.g. It is in the same directory as another reference');
            $this->assertTrue($grepper->hasReferenceTo('1.'), 'Should find 1. It is referenced whenever its children are referenced.');
        } finally {
            unlink($file1);
            unlink($file2);
            unlink($file3);
            unlink($file4);
            rmdir($path1);
            rmdir($path2);
        }
    }
}
