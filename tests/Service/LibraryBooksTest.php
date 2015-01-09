<?php 

/*
 * This file is part of the Koinos package.
 *
 * (c) Nigel Chapman <nigel@chapman.id.au> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Koinos\Tests\Service; 

use Koinos\Service\ReferenceManager;

/*
 * @see ReferenceManagerTest for generic functional tests. This tests larger,
 * selective cases using the NT/LXX library data.
 */
class LibraryBooksTest extends \PHPUnit_Framework_TestCase
{
    private $referenceManager;

    public function setUp()
    {
        $this->referenceManager = new ReferenceManager(['nt', 'lxx']); 
    }

    public function forwardMapping($map) 
    {
        $rm = $this->referenceManager; 

        foreach ($map as $key => $value) { 
            $r1 = $rm->createReferenceFromQuery($key); 
            $this->assertEquals($value, $rm->getTitle($r1));
        } 
    }

    public function reverseMapping($map)
    {
        $rm = $this->referenceManager;

        foreach ($map as $key => $value) { 
            $r2 = $rm->createReferenceFromQuery($value); 
            $this->assertEquals($key, $rm->getHandle($r2));
        }
    }

    /**
     * Simple confirmation tests using the actual nt+lxx data set from
     * the books.csv files. 
     */
	public function testTwoWayHandleToTitleConversion()
    {
        $tests = [ 
            'rev+22' => "Revelation 22",
            'rev+22.22' => "Revelation 22:22",
            'cant+3.3-4' => "Canticum 3:3-4",
            'matt+18.23-35;20.1-16' => 'Matthew 18:23-35;20:1-16',
            '1cor+7-8' => '1 Corinthians 7-8',
            'phm+7-8' => 'Philemon 7-8',
            '2jn-3jn' => "2 John-3 John",
            "matt+5.39-42,44,46-47,49" => "Matthew 5:39-42,44,46-47,49",
            "matt+5.39-42,44;8" => "Matthew 5:39-42,44;8", 
        ];
        $this->forwardMapping($tests); 
        $this->reverseMapping($tests); 
	}

    /**
     * For troubleshooting error cases as required; complete, generic unit
     * tests go in ReferenceManagerTest. 
     */
    public function testHandleToTitleConversions()
    {
        $tests = [ 
            'songofsolomon+3.3-4' => "Canticum 3:3-4",  // <-- Alias with spaces
            'matt+19.1,5,3,7' => 'Matthew 19:1,3,5,7',  //  <-- Sorting
            'matt+18;19.1,3,5,7' => 'Matthew 18:1-19:1;19:3,5,7',  //  <-- Merging
        ];
        $this->forwardMapping($tests); 
    }

    public function testMultiBookRanges()
    {
        $tests = [ 
            'rom' => "Romans",  // <-- not Romans 1-999 (issue #11)
            'rom-gal' => 'Romans-Galatians',  
            'rom 1; 1cor 13' => 'Romans 1;1 Corinthians 13',  //  <-- Not Rom 1;13 (issue #11)
        ];
        $this->forwardMapping($tests); 
    }

}
