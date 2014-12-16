# koinos

A PHP Composer Package (and Symphony Bundle) for working with:  

* biblical references in texts and databases, and 
* classical and Koine Greek text. 

Koinos means 'Common' in Ancient Greek. This package contains a number of
reusable services and utilities from the [Hexapla][hex] project. 

[hex]: http://hexap.la 

## Installation 

Add the following Packagist dependency to `composer.json`, then run `composer
update` as usual: 

    "require": {
        "eukras/koinos": "~1.0"
    }

If you wish, run the test suite with: 

    phpunit -c vendor/eukras/koinos/src/Koinos/Bundle/KoinosBundle/phpunit.xml 

The tests are the best documentation. 

## Files

    ./composer.json
    ./src/Koinos/Bundle/KoinosBundle/Utility/Greek.php
    ./src/Koinos/Bundle/KoinosBundle/Utility/Reference.php
    ./src/Koinos/Bundle/KoinosBundle/phpunit.xml
    ./src/Koinos/Bundle/KoinosBundle/Service/ReferenceManager.php
    ./src/Koinos/Bundle/KoinosBundle/KoinosBundle.php
    ./src/Koinos/Bundle/KoinosBundle/Resources/library/lxx/books.csv
    ./src/Koinos/Bundle/KoinosBundle/Resources/library/nt/books.csv
    ./src/Koinos/Bundle/KoinosBundle/Resources/config/services.yml
    ./src/Koinos/Bundle/KoinosBundle/Tests/... 


## Service\ReferenceManager and Utility\Reference

The ReferenceManager is use to construct, manipulate and format References. 

### Examples

Initialise the ReferenceManager as follows: 

    use Koinos\Bundle\KoinosBundle\Service\ReferenceManager;
    $rm = new ReferenceManager($libraries=['nt', 'lxx']); 

Libraries are easy to create: just add `Resources/library/$library/books.csv`.
The CSV lines specify id, library-name, name, short-name, handle,
reference-depth, aliases, total-chapters. Most books are reference depth 2
(chapter:verse), but single-chapter books are identified by verse only, so have
a reference depth of 1: 

    107,NT,1 Corinthians,1 Cor,1cor,2,1co,16
    118,NT,Philemon,Phm,phm,1,phl/philem,1

The ReferenceManager handles most Reference operations. 

    $mattId = $rm->matchBookName('matt');  //  (int)101
    $matt28 = $rm->createReferenceFromBookAndChapter($mattId, 28); 

    $acts1 = $rm->getNextChapterReference($matt28); 

    echo $rm->getShortTitle($matt28);  //  Matt 28
    echo $rm->getHandle($matt28);      //  matt+28

But you will normally want to work with references, which looks like this:

    $ref1 = $rm->createReferenceFromQuery('1 Cor 16:1-5,8,10-12,13-14'); 
    $ref2 = $rm->createReferenceFromQuery('1cor+16.1-4,5,8,10-14'); 

In this example `$ref1` generates identically to `$ref2`. Adjacent verse ranges
are combined together into a standard reference. Koinos does not know or care
how many verses are in a given chapter, though; internally, full-chapter links 
are treated as Book 1:1-999. 

    echo $rm->getTitle($ref1);  //  1 Corinthians 16:1-5,8,10-14 
    echo $rm->getTitle($ref2);  //  1 Corinthians 16:1-5,8,10-14

    $ref1->equals($ref2);       //  true
    $ref1->contains($ref2);     //  true

All this together allows HTML links to be generated easily, though in a
framework you would most likely plug `$rm->getHandle($ref)` in to a route. 

    echo $rm->getLink($matt28, $uriPrefix='/r/'); 

Being able to generate links allows them to be auto-replaced in text: 

    $linkedHtml = $rm->linkReferencesInHtml($textContainingReferences, '/r/'); 

The scanner for matching links in text will identify if they exist in
parallels, that is, if references are separated by a pipe character, e.g.  "The
Psalms Ps 14 | Ps 53 are the same." See the test suite for examples. 

### Utility\Reference Internals

Internal working is done with quadruples of `[book, section, chapter, verse]`;
In `$ref1`, 1cor is book #107, and has two-level referencing (c:v), so the
section number is always 1:

    echo json_encode($ref1->getRanges($asQuadruples=true)); 

    //  [ [ [107, 1, 16,  1], [107, 1, 16,  5] ], 
    //    [ [107, 1, 16,  8], [107, 1, 16,  8] ], 
    //    [ [107, 1, 16, 10], [107, 1, 16, 14] ] ]

Every quadruple becomes an `UNSIGNED INT(12)` for SQL, making it easy to
efficiently index and match references and ranges. In PHP these numbers must be
treated as a strings, or cast to a `double`: 12 digits exceed PHP's integer
length. References will normally be worked with as quadruples, though. 

    echo $ref1->getSqlClause($columnName='reference'); 

    //  (reference BETWEEN 107001016001 AND 107001016005) OR 
    //  (reference BETWEEN 107001016008 AND 107001016008) OR 
    //  (reference BETWEEN 107001016010 AND 107001016014)  

## Utility\Greek

The Greek utility performs simple manipulation and scanning of Greek text: 

    use Koinos\Bundle\KoinosBundle\Utility\Greek;
    $g = new Greek; 

This performs romanization, and offers some Unicode convenience wrappers. 

    echo $g->romanize('Ῥύγχος');   //  Rhunchos
    echo $g->romanize('Ἡσυχάζω');  //  Hēsuchazō
    echo $g->romanize('Αὑτοῖσι');  //  Hautoisi

    echo $g->length('ᾁ');          //  1
    echo $g->lowercase('Α');       //  α
    echo $g->unicodeChr('1f0c');   //  Ἄ

Here's how it would be used to scan the structure of Psalm 116 (LXX) -- in
Hexapla this is used to save texts word-by-word into a database. 

    $ps116 = "1 αλληλουια.

        αἰνεῖτε τὸν κύριον πάντα τὰ ἔθνη. ἐπαινέσατε αὐτόν πάντες οἱ λαοί.

        2 ὅτι ἐκραταιώθη τὸ ἔλεος αὐτοῦ [ἐφ’ ἡμᾶς] καὶ ἡ ἀλήθεια τοῦ κυρίου
        μένει εἰς τὸν αἰῶνα.

        Τί εἰς τέλος;";

For each word, let's grab its book/chapter/verse numbers (BCV), then
paragraph/sentence/word numbers (PSW), and then any leading or trailing
punctuation. (Psalms are book 226.) 

    $structure = $g->getStructure($ps116, 226, 116);

    //    b    c    v  p  s   w  prefix  word      suffix

    [   [ 226, 116, 1, 1, 1,  1, '', 'αλληλουια',  '.' ],
        [ 226, 116, 1, 2, 1,  1, '', 'αἰνεῖτε',    ''  ],
        [ 226, 116, 1, 2, 1,  2, '', 'τὸν',        ''  ],
        [ 226, 116, 1, 2, 1,  3, '', 'κύριον',     ''  ],
        [ 226, 116, 1, 2, 1,  4, '', 'πάντα',      ''  ],
        [ 226, 116, 1, 2, 1,  5, '', 'τὰ',         ''  ],
        [ 226, 116, 1, 2, 1,  6, '', 'ἔθνη',       '.' ],
        [ 226, 116, 1, 2, 2,  1, '', 'ἐπαινέσατε', ''  ],
        [ 226, 116, 1, 2, 2,  2, '', 'αὐτόν',      ''  ],
        [ 226, 116, 1, 2, 2,  3, '', 'πάντες',     ''  ],
        [ 226, 116, 1, 2, 2,  4, '', 'οἱ',         ''  ],
        [ 226, 116, 1, 2, 2,  5, '', 'λαοί',       '.' ],
        [ 226, 116, 2, 3, 1,  1, '', 'ὅτι',        ''  ],
        [ 226, 116, 2, 3, 1,  2, '', 'ἐκραταιώθη', ''  ],
        [ 226, 116, 2, 3, 1,  3, '', 'τὸ',         ''  ],
        [ 226, 116, 2, 3, 1,  4, '', 'ἔλεος',      ''  ],
        [ 226, 116, 2, 3, 1,  5, '', 'αὐτοῦ',      ''  ],
        [ 226, 116, 2, 3, 1,  6, '[','ἐφ’',        ''  ],
        [ 226, 116, 2, 3, 1,  7, '', 'ἡμᾶς',       ']' ],
        [ 226, 116, 2, 3, 1,  8, '', 'καὶ',        ''  ],
        [ 226, 116, 2, 3, 1,  9, '', 'ἡ',          ''  ],
        [ 226, 116, 2, 3, 1, 10, '', 'ἀλήθεια',    ''  ],
        [ 226, 116, 2, 3, 1, 11, '', 'τοῦ',        ''  ],
        [ 226, 116, 2, 3, 1, 12, '', 'κυρίου',     ''  ],
        [ 226, 116, 2, 3, 1, 13, '', 'μένει',      ''  ],
        [ 226, 116, 2, 3, 1, 14, '', 'εἰς',        ''  ],
        [ 226, 116, 2, 3, 1, 15, '', 'τὸν',        ''  ],
        [ 226, 116, 2, 3, 1, 16, '', 'αἰῶνα',      '.' ],
        [ 226, 116, 2, 4, 1,  1, '', 'Τί',         ''  ],
        [ 226, 116, 2, 4, 1,  2, '', 'εἰς',        ''  ],
        [ 226, 116, 2, 4, 1,  3, '', 'τέλος',      ';' ]   ];  

See the class and Test suites for more. 
