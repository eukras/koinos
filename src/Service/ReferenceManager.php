<?php 

/*
 * This file is part of the Koinos package.
 *
 * (c) Nigel Chapman <nigel@chapman.id.au> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Koinos\Bundle\KoinosBundle\Service; 

use Koinos\Bundle\KoinosBundle\Utility\Reference; 

/**
 * Manage reference objects for a corpus of texts. 
 *
 * Use cases: 
 *
 *  1)  Load corpus data from a library file. 
 *  2)  Return collections of books for that corpus. 
 *  3)  Use the corpus's dataset to:
 *      a)  Create DB-friendly reference objects from human- and HTML-friendly
 *          formats. 
 *      b)  Format reference objects into human- and HTML-friendly formats. 
 *
 * Note: The ReferenceManager CAN generate HTML links; for use with routing
 * systems, though, use getHandle() for URL friendly identifiers. 
 *
 */
class ReferenceManager
{ 

    /*
     * Constants for specifying format types. 
     * -----------------------------------------------------
     */

    const HANDLE        = 1;    //  '1cor'
    const TITLE         = 2;    //  '1 Corinthians'
    const SHORT_TITLE   = 3;    //  '1 Cor'

    /*
     * Mappings 
     * -----------------------------------------------------
     * Use id and alias to find BookID, then BookID to find all other info.
     *
     */

    /*
     * Map abbreviation to id 
     *
     */
    private $id = [];

    /*
     * Map id to name 
     *
     */
    private $name = [];

    /*
     * Map short name to id 
     *
     */
    private $shortName = [];

    /*
     * Map alias to id
     *
     */
    private $alias = [];

    /*
     * Map $id to library name
     *
     */
    private $library = []; 

    /*
     * Map library to [$id]
     *
     */
    private $libraries = []; 

    /*
     * Map id to lowerCaseName 
     *
     */
    private $nameLowerCase = [];

    /*
     * Map id to name 
     *
     */
    private $abbreviation = [];

    /*
     * Map $id to [$alias]
     *
     */
    private $aliases = [];

    /*
     * Map id to depth (e.g. 1 is 3Jn+1, 2 is Jn+3:3) 
     *
     */
    private $depth = [];

    /*
     * Map id -> #chapters 
     *
     */
    private $chapters = [];


    /*
     * Constructor and Configurators 
     * -----------------------------------------------------
     */

    public function __construct($libraryNames=null)
    {
        $this->clearData(); 

        if (is_array($libraryNames)) { 
            foreach($libraryNames as $name) { 
                $path = __DIR__ . "/../Resources/library/$name"; 
                if (is_dir($path)) { 
                    $this->loadCsvFile("$path/books.csv"); 
                } else { 
                    throw new \Exception("Library directory not found for '$name'.");
                }
            }
        }
    }

    /**
     * Empty all mappings. 
     * 
     * @return void
     */
    public function clearData()
    {
        $this->id               = [];
        $this->name             = [];
        $this->shortName        = [];
        $this->alias            = [];
        $this->library          = [];
        $this->nameLowerCase    = [];
        $this->abbreviation     = [];
        $this->depth            = [];
        $this->aliases          = [];
        $this->libraries        = [];
        $this->chapters         = [];
    }

    /**
     * Get CSV data from a file, load into ReferenceManager.
     * 
     * @param string $path 
     * @return void
     */
    public function loadCsvFile($path) 
    {
        $fp = fopen($path, 'r');  //  File pointer
        if ($fp == false) {
            throw new \Exception("CSV file could not be opened for '$path'."); 
        } else {
            $data = []; 
            while ($csv = fgetcsv($fp)) {
                $data[] = $csv; 
            }
            $this->loadData($data); 
            fclose($fp); 
        }

    }

    /**
     * loadData 
     * 
     * @param mixed $csv 
     * @return void
     */
    public function loadData($csv)
    {
        foreach ($csv as $row) {

            list(   $bookId,
                    $bookLibrary, 
                    $bookName,
                    $bookShortName,
                    $bookAbbreviation,
                    $bookDepth, 
                    $bookAliases,
                    $bookChapters   ) = $row; 

            $id = (int)$bookId; 

            $this->id[$bookAbbreviation] = $id;

            $shortName = str_replace(' ', '', strtolower($bookName));
            $this->addAlias($shortName, $id, $strict=true); 

            $filteredName = $this->filterQuery($bookName); 
            $this->addAlias($filteredName, $id, $strict=false); 

            if (trim(($bookAliases)) != '') {
                $aliases = explode('/', $bookAliases);
                foreach ($aliases as $alias) {
                    $filteredAlias = $this->filterQuery($alias); 
                    if ($filteredAlias != $alias) { 
                        throw new \Exception("Bad alias name: (try $filteredAlias)"); 
                    }
                    $this->addAlias(trim($alias), $id, $strict=true); 
                }
            }

            $this->library[$id] = $bookLibrary; 
            if (!isset($this->libraries[$bookLibrary])) { 
                $this->libraries[$bookLibrary] = []; 
            }
            $this->libraries[$bookLibrary][] = $id; 

            $this->name[$id]            = $bookName; 
            $this->shortName[$id]       = $bookShortName; 
            $this->depth[$id]           = (int)$bookDepth; 
            $this->chapters[$id]        = (int)$bookChapters; 
            $this->abbreviation[$id]    = $bookAbbreviation; 

        }
    }

    /*
     * Access and lookup functions 
     * -----------------------------------------------------
     */

    public function getId($name, $noExceptions=false)
    {
        $safeName = trim($name);
        if (isset($this->id[$safeName])) {
            return (int)$this->id[$safeName];
        } else if (($index = array_search($safeName, $this->nameLowercase)) !== false ) { 
            return $index;
        } else {
            if (isset($this->aliasing[$safeName])) {
                return (int)$this->aliasing[$safeName];
            } else {
                if ($noExceptions) {
                    return false;
                } else {
                    throw new \Exception("Book ID not found for '$safeName'.");
                }
            }
        }
    }

    public function getAlias($name)
    {
        $safeName = trim($name);
        if (isset($this->alias[$safeName])) {
            return $this->alias[$safeName];
        } else {
            throw new \Exception("Book abbreviation not found for #$safeId.");
        }
    }

    public function getLibrary($id)
    {
        $safeId = (int)$id;
        if (isset($this->library[$safeId])) {
            return $this->library[$safeId];
        } else {
            throw new \Exception("Book abbreviation not found for #$safeId.");
        }
    }

    public function getLibraryBooks($name=null)
    {
        if (is_string($name)) { 
            $safeName = trim($name);
            if (isset($this->libraries[$safeName])) {
                return $this->libraries[$safeName];
            } else {
                throw new \Exception("Book abbreviation not found for #$safeId.");
            }
        } else { 
            return $this->libraries; 
        }
    }

    public function getName($id)
    {
        $safeId = (int)$id;
        if (isset($this->name[$safeId])) {
            return $this->name[$safeId];
        } else {
            throw new \Exception("Book name not found for #$safeId.");
        }
    }

    public function getShortName($id)
    {
        $safeId = (int)$id;
        if (isset($this->shortName[$safeId])) {
            return $this->shortName[$safeId];
        } else {
            throw new \Exception("Book name not found for #$safeId.");
        }
    }

    public function getAbbreviation($id)
    {
        $safeId = (int)$id;
        if (isset($this->abbreviation[$safeId])) {
            return $this->abbreviation[$safeId];
        } else {
            throw new \Exception("Book abbreviation not found for #$safeId.");
        }
    }

    public function getAliases($id)
    {
        $safeId = (int)$id;
        if (isset($this->aliases[$safeId])) {
            return $this->aliases[$safeId];
        } else {
            throw new \Exception("Book aliases not found for #$safeId.");
        }
    }

    public function getDepth($id)
    {
        $safeId = (int)$id;
        if (isset($this->depth[$safeId])) {
            return (int)$this->depth[$safeId];
        } else {
            throw new \Exception("Book depth not found for #$safeId.");
        }
    }

    public function getChapters($id)
    {
        $safeId = (int)$id;
        if (isset($this->chapters[$safeId])) {
            return $this->chapters[$safeId];
        } else {
            throw new \Exception("Book chapters not found for #$safeId.");
        }
    }

    /**
     * Throw an exception if an exception is overwritten. 
     * 
     * @param string $alias 
     * @param integer $id 
     * @param boolean $id 
     * @return void
     */
    public function addAlias($alias, $id, $strict=true)
    { 
        $aliasName = trim($alias); 
        if (isset($this->alias[$aliasName])) { 
            if ($strict == true) { 
                $oldId = $this->alias[$aliasName]; 
                throw new \Exception("Alias '$aliasName' is already set for #$oldId");
            } else { 
                //  Do nothing. 
            }
        } else { 
            $this->alias[$aliasName] = $id;
            if (!isset($this->aliases[$id])) { 
                $this->aliases[$id] = [$aliasName]; 
            } else { 
                $this->aliases[$id][] = $aliasName; 
            }
        }
    }

    /*
     * Return a simple array for tag could formatting. 
     * 
     * @param int $steps 
     * @param string $libraryName 
     * @return void
     */
    public function tagCloudForNavigation($steps=5)
    {
        $tagCloud = [];
        foreach ($this->getLibraryBooks() as $libraryName => $bookIds) { 
            $libraryTagCloud = []; 
            $maxWeight = 1; 
            foreach ($bookIds as $bookId) { 
                $chapters = $this->getChapters($bookId); 
                if ($chapters < 55) { 
                    //  Ignore Psalms, basically. 
                    $maxWeight = max($maxWeight, $this->getChapters($bookId)); 
                }
            }
            $divider = $maxWeight / $steps; 
            foreach ($bookIds as $bookId) { 
                $r = new Reference;
                $r->addBookAndChapter($bookId, 1); 
                $libraryTagCloud[] = [
                    'handle' => $this->getHandle($r), 
                    'title' => $this->getShortName($bookId), 
                    'weight' => min(ceil($this->getChapters($bookId) / $divider), $steps), 
                    ]; 
            }
            $tagCloud[$libraryName] = $libraryTagCloud; 
        }
        return $tagCloud; 
    }

    /* 
     * FACTORY METHODS 
     * ------------------------------------------------------------
     */

    public function createReferenceFromRanges($ranges)
    {
        $reference = new Reference;
        $reference->addRanges($ranges); 
        return $reference;
    }

    public function createReferenceFromQuadrupleRanges($quadrupleRanges)
    {
        $r = new Reference; 
        $ranges = []; 
        foreach ($quadrupleRanges as $quadrupleRange) { 
            list($start, $end) = $quadrupleRange; 
            $r->requireValidQuadruple($start);
            $r->requireValidQuadruple($end);
            $ranges[] = [
                $r->quadrupleToIndex($start),  
                $r->quadrupleToIndex($end),  
            ]; 
        }
        return $this->createReferenceFromRanges($ranges); 
    }

    /**
     * Return a reference for a given book and chapter
     * 
     * @param int $b 
     * @param int $c 
     * @return Reference
     */
    public function createReferenceFromBookAndChapter($b, $c)
    {
        $reference = new Reference;
        $reference->addBookAndChapter($b, $c); 
        return $reference;
    }

    /**
     * Return a reference for a given book, chapter and verse. 
     * 
     * @param integer $b 
     * @param integer $c 
     * @param integer $v 
     * @return Reference
     */
    public function createReferenceFromBookChapterAndVerse($b, $c, $v)
    {
        $reference = new Reference;
        $reference->addBookChapterAndVerse($b, $c, $v); 
        return $reference;
    }

    /**
     * getChapter 
     * 
     * @param Reference $reference 
     * @return Reference|null
     */
    public function getChapterReference(Reference $reference)
    {
        if ($quadruple = $reference->getFirstQuadruple()) { 
            list($b, $s, $c, $v) = $quadruple; 
            return $this->createReferenceFromBookAndChapter($b, $c);  
        } else { 
            return null; 
        }
    }

    /**
     * Previous chapter, for navigation links.
     *
     * Navigation links can loop within each library. 
     * 
     * @param Reference $reference 
     * @return Reference|null
     */
    public function getPreviousChapterReference(Reference $reference)
    {
        if ($quadruple = $reference->getFirstQuadruple()) { 
            list($b, $s, $c, $v) = $quadruple;
            if ($c == 1) { 
                $l = $this->getLibrary($b);
                $books = $this->getLibraryBooks($l);
                $minBook = min($books);
                if ($b == $minBook) {
                    $maxBook = max($books);
                    $maxChapter = $this->getChapters($maxBook);
                    return $this->createReferenceFromBookAndChapter($maxBook, $maxChapter);  
                } else {
                    $maxChapter = $this->getChapters($b - 1);
                    return $this->createReferenceFromBookAndChapter($b - 1, $maxChapter);  
                }
            } else { 
                return $this->createReferenceFromBookAndChapter($b, $c - 1);  
            }
        } else { 
            return null; 
        }
    }

    /**
     * Next chapter, for navigation links.
     *
     * Navigation links can loop within each library. 
     * 
     * @param Reference $reference 
     * @return Reference|null
     */
    public function getNextChapterReference(Reference $reference)
    {
        if ($quadruple = $reference->getFirstQuadruple()) { 
            list($b, $s, $c, $v) = $quadruple;
            $l = $this->getLibrary($b);
            $books = $this->getLibraryBooks($l);
            $minBook = min($books);
            $maxBook = max($books);
            $maxChapter = $this->getChapters($b);
            if ($c == $maxChapter) {
                if ($b == $maxBook) {
                    return $this->createReferenceFromBookAndChapter($minBook, 1);  
                } else {
                    return $this->createReferenceFromBookAndChapter($b + 1, 1);  
                }
            } else { 
                return $this->createReferenceFromBookAndChapter($b, $c + 1);  
            }
        } else { 
            return null; 
        }
    }

    /*
     * Filtering and parsing 
     * ------------------------------------------------------------
     */

    /**
     * Pre-filter for query parsing. Reduce to an href handle.  
     * 
     * @param mixed $str 
     * @static
     * @return void
     */
    public function filterQuery($str) 
    {
        $filters = [

            'Lowercase' => function ($s) {
                return strtolower($s);
            }, 

            'Character whitelist' => function ($s) {
                $allow = "-, +;:.1234567890abcdefghijklmnopqrstuvwyxz"; 
                $out = '';
                for ($i=0; $i < strlen($s); $i++) {
                    if (strpos($allow, $s[$i]) !== false) {
                        $out .= substr($s, $i, 1);
                    }
                }
                return $out;
            },  
            
            'Trim space' => function ($s) {
                return trim($s);
            }, 

            'Normalise spacing' => function ($s) {
                return preg_replace('/[ +]+/', ' ', $s);
            }, 

            'No space when number precedes word' => function ($s) { 
                return preg_replace('/([0-9])[ +]+([a-z])/', '\\1\\2', $s); 
            }, 

            'Underscores between words' => function ($s) { 
                return preg_replace('/([a-z])[ +]+([a-z])/', '\\1_\\2', $s); 
            },

            'Space when word precedes number' => function ($s) { 
                return preg_replace('/([a-z])([0-9])/', '\\1 \\2', $s); 
            },

            'No space when non-word precedes number' => function ($s) { 
                return preg_replace('/([^a-z])[ +]+([0-9])/', '\\1\\2', $s); 
            }, 

            'Url friendly' => function ($s) {
                return strtr($s, ': ', '.+');
            }, 
        ]; 
        $query = $str; 
        foreach ($filters as $f) { 
            $query = $f($query); 
        }
        return $query; 
    }

    /**
     * Match a book ID to a book name. 
     *
     * @see self::queryToQuadrupleRanges()
     *
     * @throws User-friendly Exception 
     *
     * @idea Include a spelling suggestion in the message? 
     * 
     * @param mixed $bookName 
     * @return string
     */
    public function matchBookName($bookName) 
    {
        if (isset($this->id[$bookName])) { 
            return $this->id[$bookName]; 
        } elseif (isset($this->alias[$bookName])) { 
            return $this->alias[$bookName]; 
        } else { 
            throw new \Exception("Book not recognized: $bookName"); 
        }
    }

    /**
     * For a given book ID, construct quadruple ranges for the given range
     * numbers. 
     * 
     * @param integer $b 
     * @param string $rangeNumbers 
     * @return array of [[b, s, c, v], [b, s, c, v]]
     */
    public function getQuadrupleRanges($b, $rangeNumbers)
    {
        $this->requireValidRangeNumberString($rangeNumbers); 

        $quadrupleRanges = []; 
        $depth = $this->getDepth($b); 
        $name = $this->getName($b); 

        $lastChapter = null; 
        $lastReferenceIncludedVerses = false; 

        $parts = explode(',', $rangeNumbers); 
        foreach ($parts as $part) { 

            $numDelimiters = substr_count($part, '.'); 
            if ($numDelimiters == 2) { 
                if ($depth == 2) { 

                    //  c.v-c.v

                    $chapterRanges = explode('-', $part); 
                    $numChapterRanges = count($chapterRanges); 
                    if ($numChapterRanges == 2) { 
                        list($cv1, $cv2) = $chapterRanges; 
                        $cv1Parts = explode('.', $cv1); 
                        $numCv1Parts = count($cv1Parts); 
                        $cv2Parts = explode('.', $cv2); 
                        $numCv2Parts = count($cv2Parts); 
                        if ($numCv1Parts == 2 and $numCv2Parts == 2) { 
                            list($c1, $v1) = $cv1Parts;
                            list($c2, $v2) = $cv2Parts; 
                            $quadrupleRanges[] = [
                                [$b, 1, (int)$c1, (int)$v1], 
                                [$b, 2, (int)$c2, (int)$v2], 
                                ]; 
                            $lastReferenceIncludedVerses = true; 
                        } else { 
                            throw new \Exception("Could not understand: '$part'"); 
                        }
                    } else { 
                        throw new \Exception("Could not understand: '$part'"); 
                    }
                    $lastChapter = null;

                } else {
                    throw new \Exception("The book '$name' cannot have the reference '$part'"); 
                }

            } elseif ($numDelimiters == 1) { 

                if ($depth == 2) { 

                    //  c.v     \  ... the same case. 
                    //  c.v-v    \  

                    $parts = explode('.', $part); // == 2
                    list($c, $versesList) = $parts; 
                    $verseRanges = explode(',', $versesList); 
                    foreach ($verseRanges as $verseRange) { 
                        $rangeParts = explode('-', $verseRange); 
                        $numRangeParts = count($rangeParts); 
                        if ($numRangeParts == 1) { 
                            $v = $verseRange; 
                            $quadrupleRanges[] = [
                                [$b, 1, (int)$c, (int)$v], 
                                [$b, 1, (int)$c, (int)$v], 
                                ]; 
                            $lastReferenceIncludedVerses = true; 
                        } elseif ($numRangeParts == 2) { 
                            list($v1, $v2) = $rangeParts; 
                            $quadrupleRanges[] = [
                                [$b, 1, (int)$c, (int)$v1], 
                                [$b, 1, (int)$c, (int)$v2], 
                                ]; 
                            $lastReferenceIncludedVerses = true; 
                        } else { 
                            throw new \Exception("Could not understand: $part"); 
                        } 
                    } 
                    $lastChapter = (int)$c;

                } else { 
                    throw new \Exception("Bad reference depth $depth for $name"); 
                }

            } elseif ($numDelimiters == 0) { 

                $rangeParts = explode('-', $part); 
                $numRangeParts = count($rangeParts); 

                if ($numRangeParts == 2) { 
                    if ($lastChapter != null && $lastReferenceIncludedVerses) { 

                        //  v-v
                        list($v1, $v2) = $rangeParts; 
                        $quadrupleRanges[] = [
                            [$b, 1, $lastChapter, (int)$v1], 
                            [$b, 1, $lastChapter, (int)$v2], 
                            ]; 
                        $lastReferenceIncludedVerses = true; 

                    } elseif ($depth == 1) { 

                        //  v-v
                        list($v1, $v2) = $rangeParts; 
                        $quadrupleRanges[] = [
                            [$b, 1, 1, (int)$v1], 
                            [$b, 1, 1, (int)$v2], 
                            ]; 
                        $lastReferenceIncludedVerses = true; 

                    } elseif ($depth == 2) { 

                        //  c-c (no implicit chapter; see first clause above) 

                        list($c1, $c2) = $rangeParts; 
                        $quadrupleRanges[] = [
                            [$b, 1, (int)$c1,   1], 
                            [$b, 1, (int)$c2, 999], 
                            ]; 
                        $lastReferenceIncludedVerses = false; 

                    } else { 

                        throw new \Exception("Bad reference depth $depth for $name"); 

                    }

                } elseif ($numRangeParts == 1) { 

                    if ($lastChapter != null && $lastReferenceIncludedVerses) { 

                        //  v
                        $v = (int)$part; 
                        $quadrupleRanges[] = [
                            [$b, 1, $lastChapter, $v], 
                            [$b, 1, $lastChapter, $v], 
                            ]; 
                        $lastReferenceIncludedVerses = true; 

                    } elseif ($depth == 1) { 

                        //  v
                        $v = (int)$part; 
                        $quadrupleRanges[] = [
                            [$b, 1, 1, $v], 
                            [$b, 1, 1, $v], 
                            ]; 
                        $lastReferenceIncludedVerses = false; 

                    } elseif ($depth == 2) {

                        //  c
                        $c = (int)$part; 
                        $quadrupleRanges[] = [
                            [$b, 1, $c,   1], 
                            [$b, 1, $c, 999], 
                            ]; 
                        $lastReferenceIncludedVerses = false; 

                    } else { 

                        throw new \Exception("Bad reference depth $depth for $name"); 

                    }

                }

            } else { 

                throw new \Exception("Malformed verse numbers: $part"); 
            }
        }

        return $quadrupleRanges; 
    }

    /**
     * A range number string contains only numbers and punctuation.
     *
     * @param strign $str
     * @return void
     */
    public function isValidRangeNumberString($str)
    {
        $rangeStr = preg_replace('/[^0-9\-.,]/', '', $str);
        return $str == $rangeStr;
    }

    /**
     * Exceptino wrapper for isValidRangeNumberString 
     * 
     * @param string $str 
     * @throw Exception 
     * @return void
     */
    public function requireValidRangeNumberString($str)
    {
        if ($this->isValidRangeNumberString($str)) { 
            //  Do nothing. 
        } else { 
            throw new \Exception("Malformed range numbers: $str"); 
        }
    }

    /**
     * Take a query and return a set of quadruple ranges, ready for
     * constructing a reference. 
     *
     * e.g. In the New Testament, "Matt 5:6-7,9-10" should return: 
     *
     * [ [ [1, 1, 5, 6], [1, 1, 5,  7] ], 
     *   [ [1, 1, 5, 9], [1, 1, 5, 10] ], ]
     *
     * @param string $unfilteredQuery Query string. 
     * @param boolean $throwExceptions Or make best quesses.   
     * @return array of quadrupleRanges
     */
    public function queryToQuadrupleRanges($unfilteredQuery, $throwExceptions=false) 
    {
        $filteredQuery = $this->filterQuery($unfilteredQuery); 

        $quadrupleRanges = []; 

        //  After filtering, delimiters are: 
        //  --------------------------------
        //  ';' = QUERY SEPARATOR
        //  '_' = SPACES IN BOOK NAMES
        //  '+' = DELIMITES BOOK_NAME + RANGE_NUMBERS
        //  '.' = CHAPTER.VERSE 
        //  '-' = RANGE C.V-C.V, V-V

        $queries = array_filter(
            explode(';', $filteredQuery),
            function ($s) { 
                return trim($s, '+') != ''; 
            }
        ); 

        //  Each distinct query is either a set of ranges within a chapter, or
        //  a single, larger reference; it will must ALWAYS contain a chapter,
        //  when depth == 2. 

        $lastBook = null; 

        foreach ($queries as $query) { 

            //  $lastChapter

            $queryParts = explode('+', $query);
            $numQueryParts = count($queryParts); 
            if ($numQueryParts == 1) { 

                if ($this->isValidRangeNumberString($query)) { 

                    //  RangeNumbers ($lastBook cannot be null)

                    if (is_int($lastBook)) { 
                        $qrs = $this->getQuadrupleRanges($lastBook, $query); 
                        foreach ($qrs as $qr) { 
                            $quadrupleRanges[] = $qr; 
                        }
                    } else { 
                        if ($throwExceptions) { 
                            throw new \Exception("Missing book for '$query'"); 
                        } else { 
                            return null; 
                        }
                    }
                } else { 

                    //  BookName
                    //  BookName-BookName

                    $rangeParts = explode('-', $query); 
                    $numRangeParts = count($rangeParts); 
                    if ($numRangeParts == 1) { 
                        $b = $this->matchBookName($query); 
                        $quadrupleRanges[] = [
                            [$b,   1,   1,   1], 
                            [$b, 999, 999, 999]
                        ]; 
                        $lastBook = $b; 
                    } elseif ($numRangeParts == 2) {
                        list($bookName1, $bookName2) = $rangeParts; 
                        $b1 = $this->matchBookName($bookName1); 
                        $b2 = $this->matchBookName($bookName2); 
                        $quadrupleRanges[] = [
                            [$b1,   1,   1,   1], 
                            [$b2, 999, 999, 999]
                        ]; 
                        $lastBook = $b1; 
                    } else { 
                        if ($throwExceptions) { 
                            throw new \Exception("Could not understand: '$query'"); 
                        } else { 
                            return null; 
                        }
                    }
                }

            } elseif ($numQueryParts == 2) { 

                //  BookName+RangeNumbers

                list($bookName, $rangeNumbers) = $queryParts; 
                $b = $this->matchBookName($bookName); 
                $qrs = $this->getQuadrupleRanges($b, $rangeNumbers); 
                foreach ($qrs as $qr) { 
                    $quadrupleRanges[] = $qr; 
                }
                $lastBook = $b; 

            } else { 
                if ($throwExceptions) { 
                    throw new \Exception("Could not understand: '$query'"); 
                } else { 
                    return null; 
                }
            }

        }

        if ($lastBook == null) { 
            if ($throwExceptions) { 
                throw new \Exception("No book given in reference: '$unfilteredQuery'"); 
            } else { 
                return null; 
            }
        }

        return $quadrupleRanges; 
    }

    /**
     * Construct a reference from a URL or search query. 
     *
     * Only to be used for very short or incidental references like the
     * single-verse example in the home page. 
     * 
     * @param mixed $query 
     * @param mixed $previousReference 
     * @return void
     */
    public function createReferenceFromQuery($query, $throwExceptions=false)
    {
        $qr = $this->queryToQuadrupleRanges($query, $throwExceptions); 
        $r = $this->createReferenceFromQuadrupleRanges($qr);  
        return $r; 
    }

    /* 
     * FORMAT METHODS 
     * ------------------------------------------------------------
     */

    /**
     * Return a URL-friendly reference string
     *
     * (That's what a "handle" is.) 
     * 
     * @return string URL-friendly reference
     */
    public function getHandle(Reference $reference) 
    {
        return $this->formatReference(
            $reference,
            $labelType  = ReferenceManager::HANDLE, 
            $space      = '+',
            $delimiter  = '.'
        );
    }

    /**
     * Return a human-friendly reference string
     *
     * (That's what a "title" is.) 
     * 
     * @return string Human-friendly reference string
     */
    public function getTitle(Reference $reference) 
    {
        return $this->formatReference(
            $reference,
            $format     = ReferenceManager::TITLE, 
            $space      = ' ',
            $delimiter  = ':'
        ); 
    }

    /**
     * Return a human-friendly reference string, but without the book name. 
     *
     * @return string Human-friendly reference string
     */
    public function getShortTitle(Reference $reference)
    {
        return $this->formatReference(
            $reference,
            $format     = ReferenceManager::SHORT_TITLE, 
            $space      = ' ',
            $delimiter  = ':'
        ); 
    }

    /**
     * Combine getHandle and getTitle into an HTML anchor
     * 
     * @param int|null $previousBook 
     * @return void HTML anchor tag
     */
    public function getLink(Reference $reference, $uriPrefix='/r/')
    {
        return "<a href=\"$uriPrefix"
            . $this->getHandle($reference)
            . "\">"
            . $this->getTitle($reference)
            . "</a>";
    }

    /**
     * Combine getHandle and getShortTitle into an HTML anchor
     * 
     * @param int|null $previousBook 
     * @return void HTML anchor tag
     */
    public function getShortLink(Reference $reference, $uriPrefix='/r/')
    {
        return "<a href=\"$uriPrefix"
            . $this->getHandle($reference)
            . "\">"
            . $this->getShortTitle($reference)
            . "</a>";
    }

    /**
     * Find the right book name for a given labelType. 
     *
     * @see formatReference
     * 
     * @param int $bookId 
     * @param int $labelType 
     * @return void
     */
    public function formatBookName($bookId, $labelType)
    {
        if ($labelType == self::HANDLE) { 
            return $this->getAbbreviation($bookId); 
        } else if ($labelType == self::TITLE) { 
            return $this->getName($bookId); 
        } else if ($labelType == self::SHORT_TITLE) { 
            return $this->getShortName($bookId); 
        } else { 
            throw new \Exception("Invalid label type: $labelType"); 
        }
    }

    /**
     * Return e.g. '1' or '1:2', depending on a book's reference depth.
     *
     * @see formatReference
     * 
     * @param int $b 
     * @param int $s 
     * @param int $c 
     * @param int $v 
     * @param string $delimiter 
     * @return string
     */
    public function formatVerseNumber($b, $s, $c, $v, $delimiter)
    { 
        $depth = $this->getDepth($b); 
        if ($depth == 1) { 
            return "$v"; 
        } else if ($depth == 2) { 
            return "$c$delimiter$v"; 
        //  } else if ($depth == 3) {  //  Add this when sections are supported. 
            //  return "$s$delimiter$c$delimiter$v"; 
        } else { 
            throw new \Exception("Invalid referencing depth: $depth"); 
        }
    }

    /**
     * e.g. '3-4,7-9' or '2:3-4,7-9', depending on a book's reference depth. 
     * 
     * @see formatReference
     *
     * Assume the first range contains the book and chapter for all ranges;
     * assume that ranges have had sort() and simplify() applied. 
     * 
     * @param array $ranges [ [$start, $end], ... ]
     * @param string $delimiter ':' | '.'
     * @return string
     */
    public function formatSingleChapterVerseRanges($ranges, $delimiter) 
    {
        $r = new Reference; 

        $firstRange = reset($ranges); 
        $firstIndex = reset($firstRange); 

        list($b, $__, $c, $__) = $r->indexToQuadruple($firstIndex); 

        $depth = $this->getDepth($b); 
        if ($depth == 1) { 
            $prefix = ""; 
        } else if ($depth == 2) { 
            $prefix = "$c$delimiter"; 
        //  } else if ($depth == 3) {  //  Add this when sections are supported. 
            //  $prefix = "$s$delimiter$c$delimiter"; 
        } else { 
            throw new \Exception("Invalid referencing depth: $depth"); 
        }

        $verseRanges = []; 
        foreach ($ranges as $range) {  

            list($start, $end) = $range; 
            list($__, $__, $__, $v1) = $r->indexToQuadruple($start);
            list($__, $__, $__, $v2) = $r->indexToQuadruple($end);

            if ($v1 == $v2) { 
                $verseRanges[] = "$v1"; 
            } else { 
                $verseRanges[] = "$v1-$v2"; 
            }
            
        }

        return $prefix . join(',', $verseRanges); 
    }

    /**
     * Format a range that may span chapters (future: add sections)
     * 
     * @param int $b 
     * @param int $c1 
     * @param int $v1 
     * @param int $c2 
     * @param int $v2 
     * @param string $delimiter ':' | '.' 
     * @return string
     */
    public function formatRangeNumbers($b, $c1, $v1, $c2, $v2, $delimiter)
    { 
        $depth = $this->getDepth($b); 
        if ($depth == 1) { 
            if ($v1 == $v2) { 
                return "$v1";
            } elseif ($v1 == 1 and $v2 == 999) { 
                return "";  //  <-- Whole chapter 
            } else { 
                return "$v1-$v2";
            }
        } elseif ($c1 == $c2) { 
            if ($v1 == $v2) { 
                return "$c1$delimiter$v1";
            } elseif ($v1 == 1 and $v2 == 999) { 
                return "$c1";  //  <-- Whole chapter 
            } else { 
                return "$c1$delimiter$v1-$v2";
            }
        } else { 
            if ($v1 == 1 and $v2 == 999) { 
                return "$c1-$c2";
            } else { 
                return "$c1$delimiter$v1-$c2$delimiter$v2";
            }
        }
    }

    /**
     * Generalized link formatting function, used by getLink, etc.
     *
     * Because getHandle, getTitle and getLink (in Reference and ReferenceList)
     * share common formatting operations, they are abstracted into formatReference. 
     *
     * @todo Accommodate 3-tier referencing for classical texts. 
     *
     * @see getHandle, getTitle, getShortTitle 
     * 
     * @param string $reference 
     * @param string $labelType HANDLE | TITLE | SHORT_TITLE
     * @param string $spacer Usually ' ' for title, '+' for href (handle). 
     * @param string $delimiter Usually ':' for title, '.' for href (handle).
     * @return string Formatted reference string. 
     */
    public function formatReference(Reference $reference, $labelType, $spacer, $delimiter)
    {
        $r = new Reference; 

        //  A 'group' is a set of ranges in the same chapter, or any other
        //  single range. They start with a specified chapter. 

        $groups = $reference->groupRanges(); 

        //  Now format each group. 
        $formattedGroups = [];
        $lastBook = null; 

        foreach ($groups as $group) { 

            $ng = count($group); 

            //  echo "COUNT: $ng\n"; 

            if ($ng == 0) { 

                throw new \Exception("Groups are disordered (empty)."); 

            } elseif ($ng == 1) { 

                //  Just one range, but it may be ANY range: show chapter
                //  (sections not considered). 

                list($start, $end) = reset($group); 
                list($b1, $s1, $c1, $v1) = $r->indexToQuadruple($start);
                list($b2, $s2, $c2, $v2) = $r->indexToQuadruple($end);

                //  echo "$b1, $s1, $c1, $v1\n"; 
                //  echo "$b2, $s2, $c2, $v2\n"; 

                if ($b1 != $b2) { 

                    //  Always show both book labels and chapters, 
                    //  unless depth==1 and reference is whole-chapter. 

                    $book1 = $this->formatBookName($b1, $labelType); 
                    $book2 = $this->formatBookName($b2, $labelType); 

                    $depth1 = $this->getDepth($b1); 
                    $depth2 = $this->getDepth($b2); 

                    $b1Ref = null;
                    $b2Ref = null;

                    if ($v1 == 1 && $v2 == 999) { 

                        if ($depth2 == 1) { 
                            //  No numbers 
                            $b1Ref = $book1; 
                        } elseif ($depth2 == 2) { 
                            if ($c1 == 1 && $c2 == 999) { 
                                //  No numbers 
                                $b1Ref = $book1; 
                            } else { 
                                //  Whole chapters
                                $b1Ref = "$books1$delimiter$c1"; 
                            }
                        } else { 
                            throw new \Exception("Book depth not found for #$b.");
                        }

                        if ($depth2 == 1) { 
                            //  No numbers 
                            $b2Ref = $book2; 
                        } elseif ($depth2 == 2) { 
                            if ($c1 == 1 && $c2 == 999) { 
                                //  No numbers 
                                $b2Ref = $book2; 
                            } else { 
                                //  Whole chapters
                                $b2Ref = "$books2$delimiter$c2"; 
                            }
                        } else { 
                            throw new \Exception("Book depth not found for #$b.");
                        }

                    } else { 

                        //  Book, Chapter(?), Verse
                        $b1Ref = $book1 . $spacer . $this->formatVerseNumber(
                            $b1, $s1, $c1, $v1, $delimiter
                            ); 
                        $b2Ref = $book2 . $spacer . $this->formatVerseNumber(
                            $b2, $s2, $c2, $v2, $delimiter
                            ); 

                    }

                    if ($labelType == ReferenceManager::TITLE) { 
                        $result = "$b1Ref-$b2Ref";  // or " -- " ? 
                    } else { 
                        $result = "$b1Ref-$b2Ref"; 
                    }

                    $lastBook = $b1; 

                } else { 
                    
                    //  $b1 == $b2  

                    if ($lastBook == null) {
                        $book = $this->formatBookName($b1, $labelType);
                        $rangeNumbers = $this->formatRangeNumbers(
                            $b1, $c1, $v1, $c2, $v2, $delimiter
                            );
                        if ($rangeNumbers == "") { 
                            $result = $book;  //  Whole book. 
                        } else { 
                            $result = $book . $spacer . $rangeNumbers; 
                        }
                    } else {
                        $result = $this->formatRangeNumbers(
                            $b1, $c1, $v1, $c2, $v2, $delimiter
                            );
                    }

                    $lastBook = $b1; // still! -- ambiguous
                }

            } else { 

                //  More than one, but known to be in the same book and chapter
                //  (sections not considered). 

                list($start, $end) = reset($group); 
                list($b1, $s1, $c1, $v1) = $r->indexToQuadruple($start);
                if ($lastBook == null) { 
                    $book = $this->formatBookName($b1, $labelType);
                    $result = $book . $spacer .
                        $this->formatSingleChapterVerseRanges($group, $delimiter); 
                } else {  
                    $result = $this->formatSingleChapterVerseRanges($group, $delimiter); 
                }

                $lastBook = $b1; 
                
            }

            $formattedGroups[] = $result; 

        }

        return join(';', $formattedGroups); 
    }

    /**
     * getChapterHandleGrid 
     * 
     * @param Reference $reference 
     * @return array (2-D) of reference handle, ready for use in routes. 
     */
    public function getChapterHandleGrid(Reference $reference, $width=10)
    {
        if ($quadruple = $reference->getFirstQuadruple()) { 
            list($b, $s, $c, $v) = $quadruple; 
            $chapters = $this->getChapters($b);
            $handles = []; 
            foreach (range(1, $chapters) as $c) { 
                $r = $this->createReferenceFromBookAndChapter($b, $c); 
                $handles[] = $this->getHandle($r); 
            }
            return array_chunk($handles, $width); 
        } else { 
            return []; 
        }
    }

    /*
     * Replacing references with links in HTML text. 
     * ------------------------------------------------------------
     *
     */

    /**
     * Add links to references in HTML text. 
     * 
     * @param mixed $html 
     * @return void
     */
    public function linkReferencesInHtml($html)
    {
        $parallels = $this->matchReferencesAsParallels($html);

        //  This step will be used for indexing. 
        $parallelRanges = $this->convertParallelsToRanges($parallels);  

        $parallelLinks = $this->convertRangesToLinks($parallelRanges, $uriPrefix='/r/');

        //  Replace in exact order of matching: 
        $newHtml = $this->sequentialReplace(
            $this->flattenArray($parallels), 
            $this->flattenArray($parallelLinks), 
            $html
            ); 

        return $newHtml; 
    }

    /**
     * Generate the regular expression for matching any books. 
     *
     * @todo -- Also match references that appear in isolation?
     * 
     * @access public
     * @return void
     */
    public function buildRegExpForReferences()
    {
        $bookNames      = []; 
        $bookShortName  = []; 
        $bookAbbrevs    = []; 
        $bookAliases    = []; 

        foreach ($this->getLibraryBooks() as $library => $books) { 

            foreach ($books as $b) { 
                $bookNames[] = $this->getName($b); 
                $bookShortNames[] = $this->getShortName($b); 
                $bookAbbrevs[] = $this->getAbbreviation($b); 
                foreach ($this->getAliases($b) as $alias) { 
                    $bookAliases[] = $alias; 
                }
            }

        }

        //  Combine with longest names first, add flexible spacing. 

        $patterns = []; 
        foreach ($bookNames as $name) { 
            $patterns[] = str_replace(
                ' ', '\s+', 
                preg_quote(strtolower($name))
                ); 
        }
        foreach ($bookShortNames as $shortName) { 
            $patterns[] = str_replace(
                ' ', '\s+', 
                preg_quote(strtolower($shortName))
                ); 
        }
        foreach ($bookAbbrevs as $abbrev) { 
            $patterns[] = str_replace(
                ' ', '\s+', 
                preg_quote($abbrev)
                ); 
        }
        foreach ($bookAliases as $aliases) { 
            $patterns[] = str_replace(
                ' ', '\s+', 
                preg_quote($aliases)
                ); 
        }

        $uniquePatterns = array_unique($patterns); 
        $regexp = '/((' 
                . join('|', $uniquePatterns) 
                . ')[\\s\\.]+[\\d\\:\\;\\,\\.\\,\\-]*[\\d]+' 
                . ')/im'; 

        return $regexp; 
    }

    /**
     * Collate links separated by '|' pipes. 
     *
     * Issues: For resilience, this should not match references already within
     * links or the properties of tags. It works acceptably well as is, however. 
     * 
     * @param string $html 
     * @access public
     * @return void
     */
    public function matchReferencesAsParallels($html)
    {
        $regexp = $this->buildRegExpForReferences(); 
        $parts = preg_split($regexp, $html, $limit=-1, $flags=PREG_SPLIT_DELIM_CAPTURE); 

        //  Every 2nd/3rd keys are the matches, and we ignore the 3rds. Div3s
        //  from 0 are the split strings around them. First and last strings
        //  may be empty. If an even contains a single parallel marker (pipe
        //  character, with or without spaces) then the links on either side
        //  are to be grouped as parallels. 
        //  
        //  $a[0] => 'The link ';                       <-- IGNORE 
        //  $a[1] => 'Matt 3:16-19,23;4:5'; 
        //  $a[2] => 'Matt';                            <-- IGNORE 
        //  $a[3] => ' | ';                             <-- PARALLEL!
        //  $a[4] => 'John 4:2,7,9'; 
        //  $a[5] => 'John';                            <-- IGNORE 
        //  $a[6] => ' is a parallel link; whereas ';   <-- IGNORE
        //  $a[7] => 'Mark 4'; 
        //  $a[8] => 'Mark';                            <-- IGNORE 
        //  $a[9] => ' is not.';                        <-- IGNORE
        //  
        //  This should return: 
        //  
        //  [['Matt 3:16-19,23;4:5', 'John 4:2,7,9'], ['Mark 4']] 

        $parallels = [[]];
        foreach ($parts as $i => $string) {
            if ($i % 3 == 1) {

                //  Its a reference; add to current last array in parallels.

                $n = count($parallels); 
                $parallels[$n-1][] = $string; 

            } elseif (($i % 3 == 0) && (trim($string) != '|')) {

                //  NEITHER a reference NOR a separator for parallel
                //  references; create a new last array.

                $parallels[] = []; 

            } else { 

                //  Ignore the rest. 

            } 
        }

        //  This simple method leaves a spare [] at the end, and may leave a
        //  spare [] at the start. Clean that up...

        if (count($parallels[0]) == 0) { 
            array_shift($parallels); 
        }
        $n = count($parallels); 
        if (count($parallels[$n-1]) == 0) { 
            array_pop($parallels); 
        }

        return $parallels; 
    }

    /**
     * Convert each reference string into a set of quadruple ranges (see
     * above); otherwise retain the string... 
     *
     * Each range is a [ [[b,s,c,v], [b,s,c,v]] ... ] (see indexToQuadruple). 
     * 
     * @param array $parallels (2D)
     * @return array (2D) 
     */
    public function convertParallelsToRanges($parallels)
    {
        $setOfParallels = [];
        foreach ($parallels as $references) {
            $setOfRanges = []; 
            foreach ($references as $string) {
                $quadRanges = $this->queryToQuadrupleRanges(
                    $string, $throwExceptions=false 
                    ); 
                if (is_array($quadRanges)) {
                    $setOfRanges[] = $quadRanges;
                } else { 
                    $setOfRanges[] = $string;  
                }
            }
            $setOfParallels[] = $setOfRanges; 
        }
        return $setOfParallels; 
    }

    /**
     * Try to create HTML links from sets of parallel links. 
     *
     * TODO: Add a link for all the links in parallel (target="_blank"). 
     *
     * Each range is a [ [[b,s,c,v], [b,s,c,v]] ... ] (see indexToQuadruple). 
     * 
     * @param array $parallelRanges (2D) 
     * @return array (2D) 
     */
    public function convertRangesToLinks($parallelRanges, $uriPrefix='/r/') 
    { 
        $setOfParallels = [];
        foreach ($parallelRanges as $parallelRange) { 
            $setOfLinks = []; 
            foreach ($parallelRange as $value) { 
                if (is_array($value)) { 
                    $r = $this->createReferenceFromQuadrupleRanges($value); 
                    if ($r instanceof Reference) { 
                        $setOfLinks[] = $this->getShortLink($r, $uriPrefix); 
                    } else { 
                        $setOfLinks[] = "<b>BAD RANGE: " . json_encode($value) . "</b>"; 
                    }
                } else { 
                    $setOfLinks[] = "<del>$value</del>";
                }
            }
            $setOfParallels[] = $setOfLinks; 
        }
        return $setOfParallels; 
    } 

    /**
     * Turn a 2-d array into a 1-d array via normal looping. 
     * 
     * @param array $array (2D) 
     * @return array (1D)
     */
    public function flattenArray($array)
    {
        $new = []; 
        foreach ($array as $row) { 
            foreach ($row as $item) { 
                $new[] = $item; 
            }
        }
        return $new; 
    }

    /**
     * Efficient replace for reference matches. 
     *
     * Swap each term ONCE only, in the order in which they appear in the list,
     * which should correspond to the order in the text. Mainly used for
     * replacing matches that have been transformed in some way. The main idea
     * is to avoid performing a separate search for each of an enormous list of
     * words or links, and quicking locating each succcessive match to the
     * right place in the document. 
     * 
     * @param array $swapArray 
     * @param array $withArray 
     * @param string $inText 
     * @access public
     * @return void
     */
    public function sequentialReplace($swapArray, $withArray, $inText)
    {
        $outText = $inText; 
        $cursor = 0; 

        foreach ($swapArray as $i => $swap) { 

            $with = $withArray[$i]; 

            $swapLength = strlen($swap); 
            $withLength = strlen($with); 

            $position = strpos($outText, $swap, $cursor);

            if ($position === false) { 
                $cursor += $swapLength; 
            } else { 
                $outText = substr_replace($outText, $with, $position, $swapLength); 
                $cursor = $position + $withLength; 
            }

        }

        return $outText; 
    }

}
