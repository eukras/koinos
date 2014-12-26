<?php 

/*
 * This file is part of the Koinos package.
 *
 * (c) Nigel Chapman <nigel@chapman.id.au> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Koinos\Bundle\KoinosBundle\Tests\Service; 

use Koinos\Bundle\KoinosBundle\Service\ReferenceManager;

class ReferenceManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->referenceManager = new ReferenceManager(); // load no libraries
        $this->referenceManager->loadData([

        /*   ID
         *   :  Library Name
         *   :  :     Name
         *   :  :     :            Short Name
         *   :  :     :            :       Abbreviation
         *   :  :     :            :       :      Reference Depth
         *   :  :     :            :       :      :  Aliases
         *   :  :     :            :       :      :  :          Chapters
         *   :  :     :            :       :      :  :          :
         *   :  :     :            :       :      :  :          :
         */
            [1,'LIB','Big Book',  'Big',  'big',  2,'bg/bbk/bb',21], 
            [2,'LIB','Small Book','Small','small',1,'sm',       1 ], 

        ]); 
    }

    public function testInitialisation()
    { 
        $rm = $this->referenceManager; 

        //  IDs and Aliases
        $this->assertEquals(1, $rm->getId('big')); 
        $this->assertEquals(1, $rm->getAlias('bg')); 
        $this->assertEquals(1, $rm->getAlias('bbk')); 
        $this->assertEquals(1, $rm->getAlias('bb')); 
        $this->assertEquals(2, $rm->getId('small')); 
        $this->assertEquals(2, $rm->getAlias('sm')); 

        //  Names
        $this->assertEquals('Big Book', $rm->getName(1)); 
        $this->assertEquals('Big', $rm->getShortName(1)); 
        $this->assertEquals('Small Book', $rm->getName(2)); 
        $this->assertEquals('Small', $rm->getShortName(2)); 

        //  Library 
        $this->assertEquals('LIB', $rm->getLibrary(1)); 
        $this->assertEquals('LIB', $rm->getLibrary(2)); 
        $this->assertEquals([1,2], $rm->getLibraryBooks('LIB')); 

        //  Chapters and Depth
        $this->assertEquals(2, $rm->getDepth(1)); 
        $this->assertEquals(1, $rm->getDepth(2)); 
        $this->assertEquals(21, $rm->getChapters(1)); 
        $this->assertEquals(1, $rm->getChapters(2));
    }

    public function testReferenceBuilders()
    {
        $rm = $this->referenceManager; 

        //  Depth 2
        $big2a = $rm->createReferenceFromBookAndChapter(1,2);
        $big2b = $rm->createReferenceFromRanges([['1001002001', '1001002999']]); 
        $this->assertTrue($big2a->equals($big2b)); 

        //  Depth 1
        $small2a = $rm->createReferenceFromBookChapterAndVerse(2,1,2);
        $small2b = $rm->createReferenceFromRanges([['2001001002', '2001001002']]);
        $this->assertTrue($small2a->equals($small2b)); 
    }

    public function testBasicFormatters()
    {
        $rm = $this->referenceManager; 

        $big2 = $rm->createReferenceFromBookAndChapter(1,2);

        $this->assertEquals(
            'big+2', 
            $rm->getHandle($big2)
        );
        $this->assertEquals(
            'Big Book 2', 
            $rm->getTitle($big2)
        );
        $this->assertEquals(
            'Big 2', 
            $rm->getShortTitle($big2)
        );

        $small = $rm->createReferenceFromBookAndChapter(2,1);

        $this->assertEquals(
            'small', 
            $rm->getHandle($small)
        );
        $this->assertEquals(
            'Small Book', 
            $rm->getTitle($small)
        );
        $this->assertEquals(
            'Small', 
            $rm->getShortTitle($small)
        );
        
        $big25 = $rm->createReferenceFromBookChapterAndVerse(1,2,5);

        $this->assertEquals(
            'big+2.5', 
            $rm->getHandle($big25)
        );
        $this->assertEquals(
            'Big Book 2:5', 
            $rm->getTitle($big25)
        );
        $this->assertEquals(
            'Big 2:5', 
            $rm->getShortTitle($big25)
        );
    }

    public function testFilterQuery()
    {
        $rm = $this->referenceManager; 
        $test = [ 
            ' big   2  '    => 'big+2', 
            'Big Book 2'    => 'big_book+2', 
            'Big 2:5'       => 'big+2.5', 
            'Big 2:5, 7'    => 'big+2.5,7', 
            'Big _|/ @&2:5' => 'big+2.5', 
            '1 Big Book 7'  => '1big_book+7',  
            'Big7'          => 'big+7',  
            'Big Book-Small Book' => 'big_book-small_book',
        ];
        foreach ($test as $before => $after) { 
            $this->assertEquals(
                $after, 
                $rm->filterQuery($before)
            ); 
        }
    }

    public function testIsRangeNumberString()
    {
        $rm = $this->referenceManager; 
        $this->assertTrue($rm->isValidRangeNumberString('3.2-7,10')); 
        $this->assertFalse($rm->isValidRangeNumberString('s_+3.2-7,10')); 
    }

    public function testGetQuadrupleRanges()
    {
        $rm = $this->referenceManager;

        $this->assertEquals(
            [
                [[1, 1, 1, 1], [1, 1, 1, 1]],
            ], 
            $rm->getQuadrupleRanges(1, '1.1')
        ); 

        //  More complicated... 

        $this->assertEquals(
            [
                [[1, 1, 1,  1], [1, 1, 1,  1]],
                [[1, 1, 1,  2], [1, 1, 1,  3]],
                [[1, 1, 3,  4], [1, 1, 3,  9]],
                [[1, 1, 3, 12], [1, 1, 3, 12]],
            ], 
            $rm->getQuadrupleRanges(1, '1.1,2-3,3.4-9,12')
        ); 

        //  Once a chapter is specified, it becomes implicit. The final '12'
        //  is a verse in ch.7. A delimiter [.:] would have to be used to start
        //  referring to whole chapters again.

        $this->assertEquals(
            [
                [[1, 1, 2,  1], [1, 1, 3, 999]],
                [[1, 1, 5,  1], [1, 1, 5, 999]],
                [[1, 1, 7,  4], [1, 1, 7,   9]],
                [[1, 1, 7, 12], [1, 1, 7,  12]],
            ], 
            $rm->getQuadrupleRanges(1, '2-3,5,7.4-9,12')
        ); 

        //  Single-depth referencing: 

        $this->assertEquals(
            [
                [[2, 1, 1,  2], [2, 1, 1,  3]],
                [[2, 1, 1,  5], [2, 1, 1,  5]],
                [[2, 1, 1,  7], [2, 1, 1,  7]],
                [[2, 1, 1, 12], [2, 1, 1, 19]],
            ], 
            $rm->getQuadrupleRanges(2, '2-3,5,7,12-19')
        ); 

        //  Out of depth: 

        $this->setExpectedException('\Exception'); 
        $qr = $rm->getQuadrupleRanges($smallBookId=2, '7.4-9'); 

    }

    public function testCreateReferenceFromQuery()
    {
        $rm = $this->referenceManager; 

        $this->assertEquals(
            [
                [[1, 1, 2,  1], [1, 1, 3, 999]],
                [[1, 1, 5,  1], [1, 1, 5, 999]],
                [[1, 1, 7,  4], [1, 1, 7,   9]],
                [[1, 1, 7, 12], [1, 1, 7,  12]],
            ], 
            $rm->createReferenceFromQuery('big+2-3,5,7.4-9,12')
              ->getRanges($asQuadruples=true) 
        ); 

        $this->assertEquals(
            [
                [[2, 1, 1,  2], [2, 1, 1,  3]],
                [[2, 1, 1,  5], [2, 1, 1,  5]],
                [[2, 1, 1,  7], [2, 1, 1,  7]],
                [[2, 1, 1, 12], [2, 1, 1, 19]],
            ], 
            $rm->createReferenceFromQuery('small+2-3,5,7,12-19')
              ->getRanges($asQuadruples=true)
        ); 

	}

    public function testFormatReference()
    {

        $mapHandleToTitle = [ 
            'big+2.5'
                => "Big Book 2:5",
            'big+1.1,3-5;2.1-16'
                => 'Big Book 1:1,3-5;2:1-16',
            'big-small' 
                => "Big Book-Small Book",
            ];

        $rm = $this->referenceManager; 
        foreach ($mapHandleToTitle as $handle => $title) { 

            $r1 = $rm->createReferenceFromQuery($handle); 
            $this->assertEquals($title, $rm->getTitle($r1));

            $r2 = $rm->createReferenceFromQuery($title); 
            $this->assertEquals($handle, $rm->getHandle($r2));
        }

    }

    public function testNavigationLinks()
    {
        $rm = $this->referenceManager; 

        $big01 = $rm->createReferenceFromQuery('Big 1'); 
        $big09 = $rm->createReferenceFromQuery('Big 9'); 
        $big10 = $rm->createReferenceFromQuery('Big 10'); 
        $big21 = $rm->createReferenceFromQuery('Big 21'); 

        $small = $rm->getChapterReference(
            $rm->createReferenceFromQuery('Small') 
            ); 

        $this->assertEquals($big09, $rm->getPreviousChapterReference($big10)); 
        $this->assertEquals($big10, $rm->getNextChapterReference($big09)); 

        $this->assertEquals($big21, $rm->getPreviousChapterReference($small)); 
        $this->assertEquals($small, $rm->getNextChapterReference($big21)); 

        $this->assertEquals($small, $rm->getPreviousChapterReference($big01)); 
        $this->assertEquals($big01, $rm->getNextChapterReference($small)); 
    }

    public function testGetChapterHandleGrid()
    {
        $rm = $this->referenceManager; 
        $big09 = $rm->createReferenceFromQuery('Big 9'); 
        $grid = $rm->getChapterHandleGrid($big09, 10); 

        $this->assertEquals(10, count($grid[0])); 
        $this->assertEquals(10, count($grid[1])); 
        $this->assertEquals( 1, count($grid[2])); 
    }

    /*
     * HTML Replacement functions. 
     * ------------------------------------------------------------
     *
     */

    public function testBuildRegExpForReferences()  
    {
        $rm = $this->referenceManager; 
        $regexp = $rm->buildRegExpForReferences(); 
        $this->assertEquals(
            "/((big\s+book|small\s+book|big|small|" . 
            "bigbook|big_book|bg|bbk|bb|smallbook|small_book|sm)" . 
            "[\s\.]+[\d\:\;\,\.\,\-]*[\d]+)/im", 
            $regexp
            ); 
    }

    public function testMatchReferencesAsParallels()
    {
        $rm = $this->referenceManager; 

        $html = "The <i>link</i> Big 3:16-19,23;4:5 | Small 4 is "
              . "a parallel link; whereas sm 7 is not."; 

        $matches = $rm->matchReferencesAsParallels($html); 

        $this->assertEquals(
             [['Big 3:16-19,23;4:5', 'Small 4'],  ['sm 7']], 
             $matches
             ); 
    }

    public function testConvertParallelsToRanges()
    {
        $rm = $this->referenceManager;

        $ranges = $rm->convertParallelsToRanges([ 
            [
                'Big 1:1-2',
                'Small 1-2',
            ],
            [
                'Small 3-4',
            ],
        ]); 

        $correctRanges = [
            [
                [ [[1, 1, 1, 1], [1, 1, 1, 2]] ],
                [ [[2, 1, 1, 1], [2, 1, 1, 2]] ],
            ],
            [
                [ [[2, 1, 1, 3], [2, 1, 1, 4]] ], 
            ],
        ];

        $this->assertEquals($correctRanges, $ranges);
    }

    public function testConvertRangesToLinks()
    {
        $rm = $this->referenceManager; 

        $this->assertEquals(
            [ 
                [
                    '<a href="/r/big+1.1-2">Big 1:1-2</a>', 
                    '<a href="/r/small+1-2">Small 1-2</a>', 
                ], 
                [ 
                    '<a href="/r/small+3-4">Small 3-4</a>', 
                ], 
            ], 
            $rm->convertRangesToLinks(
                [ 
                    [ 
                        [ [[1, 1, 1, 1], [1, 1, 1, 2]] ], 
                        [ [[2, 1, 1, 1], [2, 1, 1, 2]] ], 
                    ], 
                    [ 
                        [ [[2, 1, 1, 3], [2, 1, 1, 4]] ],  
                    ], 
                ], 
                $prefix='/r/'
            ) 
        ); 
    }

    public function testFlattenArray()
    {
        $rm = $this->referenceManager; 

        $this->assertEquals(
            [1,2,3,4,5,6], 
            $rm->flattenArray(
                //  2D only.
                [[1,2,3], [4,5], [6], []]
                ) 
            ); 
    }

    public function testSequentialReplace()
    {
        $rm = $this->referenceManager; 

        $this->assertEquals(
            "x y z", 
            $rm->sequentialReplace(
                ['1', '2', '3'], 
                ['x', 'y', 'z'], 
                "1 2 3"
                )
            ); 

        $this->assertEquals(
            "x y z", 
            $rm->sequentialReplace(
                ['1', '1', '1'], 
                ['x', 'y', 'z'], 
                "1 1 1"
                )
            ); 

        $this->assertEquals(
            "x y z", 
            $rm->sequentialReplace(
                ['123', '12', '1'], 
                ['x', 'y', 'z'], 
                "123 12 1"
                )
            ); 

        //  Each search begins at the end of the previous substitution.
        $this->assertEquals(
            "x23 y 1", 
            $rm->sequentialReplace(
                ['1', '12', '123'], 
                ['x', 'y', 'z'], 
                "123 12 1"
                )
            ); 

    }

    /**
     * Finally: This is what it's all about.  
     *
     * @todo Expand linker to prevent matches within existing tags. 
     */
    public function testLinkReferencesInHtml()
    {
        $rm = $this->referenceManager; 

        $links = $rm->linkReferencesInHtml(
            "<p>(1) Big 6:27-36 | Big 5:44,39-42;7:12;5:46-47,45,48.</p>\n" .  
            "<p>(2) Big 6:37-38; Big\nBook 7:1-2.</p>\n"
            ); 

        $correctLinks = "<p>(1) " .
            "<a href=\"/r/big+6.27-36\">Big 6:27-36</a> | " .
            "<a href=\"/r/big+5.39-42,44-48;7.12\">Big 5:39-42,44-48;7:12</a>." .
            "</p>\n" .
            "<p>(2) " .
            "<a href=\"/r/big+6.37-38\">Big 6:37-38</a>; " .
            "<a href=\"/r/big+7.1-2\">Big 7:1-2</a>." .
            "</p>\n"; 

        $this->assertEquals($correctLinks, $links); 
    }
}

