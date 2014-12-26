<?php

/*
 * This file is part of the Koinos package.
 *
 * (c) Nigel Chapman <nigel@chapman.id.au> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Koinos\Bundle\KoinosBundle\Test\Utility; 

use Koinos\Bundle\KoinosBundle\Utility\Reference; 

class ReferenceTest extends \PHPUnit_Framework_TestCase
{
    /*
     * Basic architecture and sanity checks
     */

    public function testIsValidQuadruple()
    {
        $r = new Reference; 

        $this->assertFalse($r->isValidQuadruple([])); 
        $this->assertFalse($r->isValidQuadruple([1])); 
        $this->assertFalse($r->isValidQuadruple([1,1,1])); 
        $this->assertFalse($r->isValidQuadruple([1,1,1,1,1])); 
        $this->assertFalse($r->isValidQuadruple([0,0,0,0])); 

        $this->assertTrue( $r->isValidQuadruple([1,1,1,1])); 
        $this->assertTrue( $r->isValidQuadruple([999,999,999,999])); 

        $this->assertFalse($r->isValidQuadruple([1000,1000,1000,1000])); 
    }

    public function testRequireValidQuadruple()
    {
        $this->setExpectedException('\Exception'); 

        $r = new Reference; 
        $r->requireValidQuadruple([-1, 0, '1', 1000]); 
    }

    public function testIsValidRange() 
    {
        $r = new Reference; 

        $pass = 2002002002; 
        $fail1 = 2002002; 
        $fail2 = 2002002002002; 

        $this->assertFalse($r->isValidRange([])); 
        $this->assertFalse($r->isValidRange([0])); 
        $this->assertFalse($r->isValidRange([0, 0, 0])); 

        $this->assertTrue($r->isValidRange([$pass, $pass])); 

        $this->assertFalse($r->isValidRange([$pass, $fail1])); 
        $this->assertFalse($r->isValidRange([$fail2, $pass])); 
    }

    public function testRequireValidRange()
    {
        $this->setExpectedException('\Exception'); 

        $r = new Reference; 
        $r->requireValidRange(['INVALID', 'INVALID']); 
    }

    public function testIndexToQuadruple()
    {
        $r = new Reference; 
        $this->assertEquals(
            [1, 1, 1, 1], 
            $r->indexToQuadruple(  '1001001001')
        );
        $this->assertEquals(
            [999, 999, 999, 999], 
            $r->indexToQuadruple('999999999999')
        );
    }

    public function testQuadrupleToIndex()
    {
        $r = new Reference; 
        $this->assertEquals(
            $r->quadrupleToIndex([1, 1, 1, 1]),
            '1001001001'
        );
        $this->assertEquals(
            $r->quadrupleToIndex([999, 999, 999, 999]),
            '999999999999'
        );
    }

    public function testIndexesAreAdjacent()
    { 
        $r = new Reference; 
        $this->assertTrue(
            $r->indexesAreAdjacent(
                $r->quadrupleToIndex([1,1,1,1]), 
                $r->quadrupleToIndex([1,1,1,2]) 
            ) 
        ); 
        $this->assertFalse(
            $r->indexesAreAdjacent(
                $r->quadrupleToIndex([1,1,1,1]), 
                $r->quadrupleToIndex([1,1,1,3]) 
            ) 
        ); 
        $this->assertTrue(
            $r->indexesAreAdjacent(
                $r->quadrupleToIndex([1,1,1,999]), 
                $r->quadrupleToIndex([1,1,2,1]) 
            ) 
        ); 
    }

    public function testBasicSortAndSimplify()
    {
        $range1 = [ '1001001010', '1001001018' ]; 
        $range2 = [ '1001001001', '1001001005' ]; 

        $r1 = new Reference; 
        $r1->addRanges([$range1, $range2]);

        $r2 = new Reference; 
        $r2->addRanges([$range2, $range1]); 

        $this->assertEquals(2, $r1->countRanges()); 
        $this->assertEquals(2, $r2->countRanges()); 

        $this->assertEquals($r1->getRanges(), $r2->getRanges()); 

        $this->assertEquals(
            [
               [[ 1, 1, 1,  1 ], [ 1, 1, 1,  5 ]], 
               [[ 1, 1, 1, 10 ], [ 1, 1, 1, 18 ]],
            ], 
            $r1->getRanges($asQuadruples=true) 
        ); 
        $this->assertEquals(
            [
               [[ 1, 1, 1,  1 ], [ 1, 1, 1,  5 ]], 
               [[ 1, 1, 1, 10 ], [ 1, 1, 1, 18 ]],
            ], 
            $r2->getRanges($asQuadruples=true) 
        ); 
    }

    public function testSimplifyContaining()
    {
        $r = new Reference; 

        $r->addRanges([[ '1001001010', '1001001018' ]]);
        $r->addRanges([[ '1001001011', '1001001016' ]]);

        $this->assertEquals(
            [[ '1001001010', '1001001018' ]],
            $r->getRanges() 
        ); 
    }

    public function testSimplifyOverlapping()
    {
        $r = new Reference; 

        $r->addRanges([[ '1001001010', '1001001015' ]]);
        $r->addRanges([[ '1001001012', '1001001018' ]]); 

        $this->assertEquals(
            [[ '1001001010', '1001001018' ]], 
            $r->getRanges() 
        ); 

        //  Reverse
        $r = new Reference; 

        $r->addRanges([[ '1001001012', '1001001018' ]]); 
        $r->addRanges([[ '1001001010', '1001001015' ]]);

        $this->assertEquals(
            [[ '1001001010', '1001001018' ]], 
            $r->getRanges() 
        ); 
    }


    public function testSimplifyAdjacent()
    {
        $r = new Reference; 

        $r->addRanges([[ '1001001001', '1001001003' ]]);
        $r->addRanges([[ '1001001004', '1001001005' ]]);  

        $this->assertEquals(
            [[ '1001001001', '1001001005' ]], 
            $r->getRanges() 
        ); 

        //  Reverse 
        $r = new Reference; 

        $r->addRanges([[ '1001001004', '1001001005' ]]);  
        $r->addRanges([[ '1001001001', '1001001003' ]]);

        $this->assertEquals(
            [[ '1001001001', '1001001005' ]], 
            $r->getRanges() 
        ); 

        //  Adjacent Chapters 
        $r = new Reference; 

        $r->addRanges([[ '1001001001', '1001001999' ]]);
        $r->addRanges([[ '1001002001', '1001002999' ]]);  

        $this->assertEquals(
            [[ '1001001001', '1001002999' ]], 
            $r->getRanges() 
        ); 
    }

    public function testConvenience()
    {
        //  Book 
        $r = new Reference; 
        $r->addBook(1); 
        $this->assertEquals(
            [['1001001001', '1999999999']],
            $r->getRanges() 
            ); 

        //  Chapter
        $r = new Reference; 
        $r->addBookAndChapter(1, 2); 
        $this->assertEquals(
            [['1001002001', '1001002999']],
            $r->getRanges() 
            ); 

        //  Chapter Range
        $r = new Reference; 
        $r->addMultiChapterRange(1, 2, 1, 3, 15); 
        $this->assertEquals(
            [['1001002001', '1001003015']],
            $r->getRanges() 
            ); 

        //  Verse
        $r = new Reference; 
        $r->addBookChapterAndVerse(1, 2, 6); 
        $this->assertEquals(
            [['1001002006', '1001002006']], 
            $r->getRanges() 
            ); 

        //  Verses
        $r = new Reference; 
        $r->addBookChapterAndVerseRange(1, 2, 6, 11); 
        $this->assertEquals(
            [['1001002006', '1001002011']],
            $r->getRanges() 
            ); 
    }

    public function testEquals()
    {
        $r1 = new Reference; 
        $r1->addBookChapterAndVerseRange(1, 2, 6, 7); 

        $r2 = new Reference; 
        $r2->addBookChapterAndVerseRange(1, 2, 6, 7); 

        $r3 = new Reference; 
        $r3->addBookChapterAndVerseRange(1, 2, 6, 8); 

        $this->assertTrue($r1->equals($r2)); 
        $this->assertFalse($r1->equals($r3)); 
    }

    public function testContains()
    {
        $r1 = new Reference; 
        $r1->addBookChapterAndVerseRange(1, 2, 6, 7); 

        $r2 = new Reference; 
        $r2->addBookChapterAndVerseRange(1, 2, 6, 7); 

        $r3 = new Reference; 
        $r3->addBookChapterAndVerseRange(1, 2, 4, 8); 

        $r4 = new Reference; 
        $r4->addBookChapterAndVerseRange(1, 2, 6, 9); 

        $this->assertTrue($r1->contains($r2)); 
        $this->assertTrue($r3->contains($r1)); 
        $this->assertFalse($r1->contains($r3)); 
        $this->assertTrue($r4->contains($r1)); 
        $this->assertFalse($r1->contains($r4)); 

        $r5 = new Reference; 
        $r5->addBook(1); 

        $r6 = new Reference; 
        $r6->addBookAndChapter(1, 2); 

        $this->assertTrue($r5->contains($r1)); 
        $this->assertTrue($r6->contains($r1)); 

    }

    public function testGroupRanges()
    {
        $r1 = new Reference; 
        $r1->addBookChapterAndVerseRange(1, 2, 6, 7); 
        $r1->addBookChapterAndVerseRange(1, 2, 10, 14); 
        $r1->addBookChapterAndVerseRange(1, 3, 2, 4); 

        $groups = $r1->groupRanges();
        $this->assertEquals(2, count($groups)); 
        list($group1, $group2) = $groups;
        $this->assertEquals(2, count($group1)); 
        $this->assertEquals(1, count($group2)); 

        //  Overlapping chapter...
        $r2 = new Reference; 
        $r2->addBookAndChapter(1, 2); 
        $r2->addBookChapterAndVerse(1, 3, 1); 
        $r2->addBookChapterAndVerseRange(1, 3, 7, 8); 

        $groups = $r2->groupRanges();
        $this->assertEquals(2, count($groups)); 
        list($group1, $group2) = $groups;
        $this->assertEquals(1, count($group1)); 
        $this->assertEquals(1, count($group2)); 

        // matt+18;19.1,3,5,7 -> 18:1-19:1; 19:3,5,7
        $r3 = new Reference; 
        $r3->addBookAndChapter(1, 18); 
        $r3->addBookChapterAndVerse(1, 19, 1); 
        $r3->addBookChapterAndVerse(1, 19, 3); 
        $r3->addBookChapterAndVerse(1, 19, 5); 
        $r3->addBookChapterAndVerse(1, 19, 7); 

        $groups = $r3->groupRanges();
        $this->assertEquals(2, count($groups)); 
        list($group1, $group2) = $groups;
        $this->assertEquals(1, count($group1)); 
        $this->assertEquals(3, count($group2)); 
    }
}
