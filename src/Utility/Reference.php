<?php 

/*
 * This file is part of the Koinos package.
 *
 * (c) Nigel Chapman <nigel@chapman.id.au> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Koinos\Utility; 

/**
 * Reference 
 *
 * A DB-friendly list of verse ranges
 *
 * In the Koinos Bundle, References are usually constructed and formatted by
 * Koinos\Service\ReferenceManager. 
 *
 * ---------
 * Use Cases 
 * ---------
 *
 * 1) Store and manipulate references. 
 *   a) Support 1- and 2-level reference depth (i.e. 1jn+1 / jn+3.1). 
 *   b) Add 3-level when needed. 
 * 2) Store and retrieve references in a database in a numerical format that
 *   allows fast database retrieval indexing. 
 * 3) Compare references. 
 * 4) Automatically merge overlapping or adjacent references. 
 *
 * --------------
 * Implementation
 * --------------
 * 
 * If Matthew 19:1-6,10-14 was given to Service\ReferenceManager, and it used
 * book ID #1 for Matthew, then it should construct a Reference with the
 * following ranges, stored as index numbers. These allow efficient database
 * querying.  
 *
 * [ [ 001001019001, 001001019006 ], 
 *   [ 001001019010, 001001019014 ] ]
 *
 * Indexes will usually be converted into book/section/chapter/verse quadruples
 * for internal working:  
 *
 * [ [ [1, 1, 19,  1], [1, 1, 19,  6] ], 
 *   [ [1, 1, 19, 10], [1, 1, 19, 14] ] ]
 *
 * We neither know nor care how many verses exist in a chapter, or whether they
 * are continuous.  When setting a whole block, the end-point is assigned as
 * 999. We assume that a block won't have a thousand or more units; this will
 * match any ranges in the DB; querying will retrieve whatever verses exist. 
 *
 * Matt 19 = [[1001019001, 1001019999]] 
 * Matthew = [[1001001001, 1999999999]] 
 *
 * --------- 
 * Important 
 * ---------
 *
 * @note PHP integers max out at 11 digits, so we'll generally keep index
 * values as strings: NEVER cast to integer, or rounding will cause strange
 * effects: use (double) or process them as strings. 
 *
 * ------
 * Future
 * ------
 *
 * - Splitters, to break ranges down into subsets of references (e.g. by book
 * or chapter)
 * - Range subtraction or intersection would be nice, but not urgent.  
 *
 */

class Reference
{
    /**
     * Numeric ranges represented by this reference (always kept sorted by
     * start and end values). 
     */
    private $ranges = []; 

    /**
     * Validate an array of [bookId, sectionNum, chapterNum, verseNum]. 
     * 
     * @param array $quadruple of four integers, 1-999
     * @access public
     * @return boolean
     */
    public function isValidQuadruple($quadruple) 
    {
        if (!is_array($quadruple)) { 
            return false; 
        }
        $nq = count($quadruple);  
        if ($nq != 4) { 
            return false; 
        } 
        foreach ($quadruple as $value) { 
            if (!is_int($value)) { 
                return false;
            }
            if ($value < 1 or $value > 999) { 
                return false;
            }
        }
        return true; 
    }

    /**
     * Test for valid input range.  
     * 
     * @param mixed $range 
     * @access public
     * @return void
     */
    public function isValidRange($range) 
    {
        if (is_array($range)) { 
            if (count($range) == 2) { 
                list($start, $end) = $range;
                if ($start >= 1001001001 and $start <= 999999999999) { 
                    if ($end >= 1001001001 and $end <= 999999999999) { 
                        return true; 
                    }
                }
            }
        }
        return false; 
    }

    /**
     * Throw an exception if quadruple is not valid. 
     * 
     * @throws \Exception
     * @param array $quadruple 
     * @access public
     * @return void
     */
    public function requireValidQuadruple($quadruple)
    {
        if ($this->isValidQuadruple($quadruple)) { 
            //  Do nothing. 
        } else {
            throw new \Exception("Invalid quadruple: " . json_encode($quadruple));
        }
    }

    /**
     * Throw an exception if range is not valid. 
     *
     * @throws \Exception
     * @param array $range 
     * @access public
     * @return void
     */
    public function requireValidRange($range)
    {
        if ($this->isValidRange($range)) { 
            //  Do nothing. 
        } else { 
            throw new \Exception("Invalid range: " . json_encode($range));
        }
    }

    /**
     * Convert a reference's quadruple form to it's index number (as a string). 
     *
     * 
     * @param int $b 1-999
     * @param int $s 1-999 (unused) 
     * @param int $c 1-999
     * @param int $v 1-999
     * @access public
     * @return string
     */
    public function quadrupleToIndex($quadruple) 
    {
        $this->requireValidQuadruple($quadruple); 
        list($b, $s, $c, $v) = $quadruple; 
        $num = $b 
             . str_pad($s, 3, '0', STR_PAD_LEFT)
             . str_pad($c, 3, '0', STR_PAD_LEFT)
             . str_pad($v, 3, '0', STR_PAD_LEFT);
        return $num;
    }

    /**
     * Convert a reference's index number (as a string) to it's quadruple form. 
     *
     * e.g. '001001004015' -> array(1, 1, 4, 15) -> Matthew 4:15
     * 
     * @param string $n 
     * @access public
     * @return array
     * @throws \Exception if the reference number is bad. 
     */
    public function indexToQuadruple($index)
    {
        $pattern = "/^(\d{1,3})(\d{3})(\d{3})(\d{3})$/";
        if (preg_match($pattern, "$index", $matches)) {
            list($match, $b, $s, $c, $v) = $matches;
        } else {
            throw new \Exception("Bad index number: $index");
        }
        $quadruple = [(int)$b, (int)$s, (int)$c, (int)$v];
        $this->requireValidQuadruple($quadruple); 
        return $quadruple; 
    }

    /**
     * Sorting ranges is always by first then second index in each tuple. 
     * 
     * @access public
     * @return void
     */
    public function sort()
    {
        $rangeCompare = function($range1, $range2)
        { 
            list($start1, $end1) = $range1; 
            list($start2, $end2) = $range2; 
            if ($start1 < $start2) {
                return -1; 
            } elseif ($start1 == $start2) { 
                if ($end1 < $end2) { 
                    return -1; 
                } else { 
                    return 1; 
                }
            } else { 
                //  $start1 > $start2
                return 1; 
            }
        }; 
        uasort($this->ranges, $rangeCompare); 
    }

    /**
     * Are two indexes adjacent, and so combinable?
     *
     * This does not know or consider how many verses exist in a chapter, 
     * or whether specific verses don't exist for one reason or another. 
     * 
     * @param string $index1 
     * @param string $index2 
     * @access public
     * @return void
     */
    public function indexesAreAdjacent($index1, $index2)
    {
        list($b1, $s1, $c1, $v1) = $this->indexToQuadruple($index1);
        list($b2, $s2, $c2, $v2) = $this->indexToQuadruple($index2);

        $versesAreAdjacent = (($c1 == $c2) && ($v1 + 1 == $v2));
        $chaptersAreAdjacent = (($c1 + 1 == $c2) and ($v1 == 999 and $v2 == 1)); 

        return $versesAreAdjacent or $chaptersAreAdjacent; 
    }

    /**
     * Merge any overlapping ranges. 
     *
     * Like *nix uniq, this presupposes $this->sort() for sensible results, but
     * allows the calling program to decide when that happens. 
     *
     */
    public function simplify()
    {
        foreach ($this->ranges as $i => $range) { 
            if (isset($newRanges)) { 
                $lastRange = end($newRanges); 
                list($start1, $end1) = $lastRange;
                list($start2, $end2) = $range;
                $adjacent = $this->indexesAreAdjacent($end1, $start2); 
                $overlapping = $end1 >= $start2; 
                if ($adjacent or $overlapping) { 
                    array_pop($newRanges); 
                    $newRanges[] = [$start1, max($end1, $end2)]; 
                } else { 
                    //  Add a new range. 
                    $newRanges[] = $range; 
                }
            } else { 
                //  Initialise 
                $newRanges = [$range]; 
            }
        }

        $this->ranges = $newRanges; 
    }

    /**
     * Accessor 
     * 
     * @access public
     * @return array [ [ start, end ], ... ] 
     */
    public function getRanges($asQuadruples=false) 
    {
        if ($asQuadruples == true) { 
            $quadrupleRanges = []; 
            foreach ($this->ranges as $range) { 
                list($start, $end) = $range;
                $quadrupleRanges[] = [
                    $this->indexToQuadruple($start), 
                    $this->indexToQuadruple($end), 
                ]; 
            }
            return $quadrupleRanges; 
        } else { 
            return $this->ranges; 
        }
    }

    /**
     * Simple way to grab $b, $c within this reference.  
     * 
     * @return array|null
     */
    public function getFirstQuadruple()
    {
        $firstRange = reset($this->ranges); 
        if (is_array($firstRange)) { 
            list($start, $end) = $firstRange; 
            return $this->indexToQuadruple($start);
        } else { 
            return null;
        }
    }

    /**
     * Clear existing ranges and replace with new. 
     * 
     * @param array $ranges 
     * @access public
     * @return void
     */
    public function setRanges($ranges) 
    {
        $this->ranges = []; 
        $this->addRanges($ranges); 
    }

    /**
     * Add single ranges with [$range]. 
     * 
     * @param array $ranges 
     * @access public
     * @return void
     */
    public function addRanges($ranges) 
    {
        if (!is_array($ranges)) { 
            throw new \Exception("Array of ranges required.");
        }
        foreach ($ranges as $range) {
            $this->requireValidRange($range); 
            $this->ranges[] = $range; 
        }
        $this->sort(); 
        $this->simplify(); 
    }

    /**
     * Conveience wrapper for addRanges. 
     * 
     * @param array $range Tuple. 
     * @access public
     * @return void
     */
    public function addRange($range)
    {
        $this->addRanges([$range]); 
    }

    /**
     * Return the number of ranges in this reference. 
     * 
     * @access public
     * @return void
     */
    public function countRanges()
    {
        return count($this->ranges); 
    }

    /**
     * Directly add a range, from quadruple values; probably the most useful
     * builder function, though see the convenience methods below.  
     * 
     * @access public
     * @return void
     */
    public function addRangeByQuadruples($quadruple1, $quadruple2) 
    {
        $this->requireValidQuadruple($quadruple1); 
        $this->requireValidQuadruple($quadruple2); 

        list($b1, $s1, $c1, $v1) = $quadruple1;
        list($b2, $s2, $c2, $v2) = $quadruple2;

        //  Require b1 =< b2, s1 =< s2, c1 =< c2, v1 =< v2, except where higher
        //  tiers are already spanning units. 

        if ($b1 > $b2) { 
            throw new \Exception("Bad book range: $b1 > $b2");
        } elseif ($b1 == $b2) { 
            if ($s1 > $s2) { 
                throw new \Exception("Bad section range: $s1 > $s2");
            } elseif ($s1 == $s2) { 
                if ($c1 > $c2) { 
                    throw new \Exception("Bad chapter range: $c1 > $c2");
                } elseif ($c1 == $c2) { 
                    if ($v1 > $v2) { 
                        throw new \Exception("Bad verse range: $v1 > $v2");
                    }
                }
            }
        }

        $start = $this->quadrupleToIndex($quadruple1); 
        $end = $this->quadrupleToIndex($quadruple2); 
        $range = [$start, $end]; 
        $this->requireValidRange($range); 

        $this->ranges[] = $range; 
        $this->sort();
        $this->simplify();
    }

    /*
     * Convenience methods 
     * ------------------------------------------------------------
     */

    public function addBook($b)
    { 
        $this->addRangeByQuadruples(
            [  $b,   1,   1,   1 ],  //  <-- start  
            [  $b, 999, 999, 999 ]   //  <-- end 
        ); 
    }

    public function addBookAndChapter($b, $c)
    { 
        $this->addRangeByQuadruples(
            [  $b,   1,  $c,   1 ],  //  <-- start  
            [  $b,   1,  $c, 999 ]   //  <-- end 
        ); 
    }

    public function addBookChapterAndVerse($b, $c, $v)
    { 
        $this->addRangeByQuadruples(
            [  $b,   1,  $c,  $v ],  //  <-- start  
            [  $b,   1,  $c,  $v ]   //  <-- end 
        ); 
    }

    public function addBookChapterAndVerseRange($b, $c, $v1, $v2)
    { 
        $this->addRangeByQuadruples(
            [  $b,   1,  $c, $v1 ],  //  <-- start  
            [  $b,   1,  $c, $v2 ]   //  <-- end 
        ); 
    }

    public function addMultiChapterRange($b, $c1, $v1, $c2, $v2)
    { 
        $this->addRangeByQuadruples(
            [  $b,   1, $c1, $v1 ],  //  <-- start  
            [  $b,   1, $c2, $v2 ]   //  <-- end 
        ); 
    }

    /*
     * Comparators 
     * ------------------------------------------------------------
     */

    /**
     * $ref1 equals $ref2 if they have the same ranges. 
     * 
     * @param Reference $otherReference 
     * @access public
     * @return string clause for SQL WHERE statement 
     */
    public function equals(Reference $other)
    {
        return $this->getRanges() == $other->getRanges(); 
    }

    /**
     * $ref1 contains $ref2 if they merge and $ref1 results.
     * 
     * @param Reference $otherReference 
     * @access public
     * @return string clause for SQL WHERE statement 
     */
    public function contains(Reference $other)
    {
        $r = new Reference;
        $r->addRanges($this->getRanges()); 
        $r->addRanges($other->getRanges());
        return $this->equals($r); 
    }
    
    /**
     * Group references for formatting. A group either contains a set of
     * references in the same book and chapter, or it contains any one
     * reference of a different kind. 
     *
     * @access public
     * @return void
     */
    public function groupRanges()
    {
        $groups = []; 
        foreach ($this->ranges as $range) { 

            if (count($groups) == 0) { 

                //  Initial case 
                $groups[] = [$range]; 

            } else { 

                $lastGroup = end($groups); 
                $lastRange = reset($lastGroup); 

                list($lastStart, $lastEnd) = $lastRange; 
                list($lbs, $lss, $lcs, $lvs) = $this->indexToQuadruple($lastStart);
                list($lbe, $lse, $lce, $lve) = $this->indexToQuadruple($lastEnd);

                list($start, $end) = $range; 
                list($bs, $ss, $cs, $vs) = $this->indexToQuadruple($start);
                list($be, $se, $ce, $ve) = $this->indexToQuadruple($end);

                if ($bs < $lbe) { 
                    throw new \Exception("Ranges are disordered for books."); 
                } elseif ($bs == $lbe) { 
                    //  same book
                    if ($cs < $lce) { 
                        throw new \Exception("Ranges are disordered for chapters."); 
                    } elseif ($cs == $lce) { 

                        //  Same book and chapter -- they go together.
                        //  Unless last range was not a single-chapter range.

                        if ($lbs != $lbe || $lcs != $lce) { 

                            //  Last is not mergeable.
                            $groups[] = [$range]; 

                        } else { 

                            //  Merge with last reference... 
                            $lastGroup = array_pop($groups);
                            $lastGroup[] = $range; 
                            $groups[] = $lastGroup; 
                        }

                    } else { 
                        //  $cs > $lce  //  new chapter
                        $groups[] = [$range]; 
                    }
                } else { 
                    //  $bs > $lbe  //  new book
                    $groups[] = [$range]; 
                }

            }
        }

        return $groups; 
    }

    /*
     * Utility functions for database interaction 
     * ------------------------------------------------------------
     * @todo Move to an adapter class. 
     *
     */

    /**
     * Return an SQL clause selecting records within this reference. 
     * 
     * @param string $column SQL column name
     * @access public
     * @return string clause for SQL WHERE statement 
     */
    public function getSqlClause($column='reference')
    {
        $clauses = array();
        foreach ($this->ranges as $range) {
            list($start, $end) = $range;
            $clauses[] = "$column BETWEEN $start AND $end";
        }
        return '(' . join(') OR (', $clauses) . ')';
    }

    /**
     * Return an SQL clause selecting records whose ranges fall within this
     * range, i.e. between two references. 
     * 
     * @param string $col1 SQL column name
     * @param string $col2 SQL column name
     * @access public
     * @return string clause for SQL WHERE statement 
     */
    public function getSqlRangeClause($col1='rangeBegins', $col2='rangeEnds')
    {
        $clauses = array();
        foreach ($this->ranges as $range) {
            list($start, $end) = $range;
            $clauses[] = "$col1 >= $start AND $col2 <= $end";
        }
        return '(' . join(') OR (', $clauses) . ')';
    }
}
