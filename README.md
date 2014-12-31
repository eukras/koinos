<img src="http://hexap.la/images/logo-koinos-400w.jpg" alt="Koinos Logo"  width="400px"/>

# koinos

A PHP library and composer package for working with:  

* Biblical references in texts and databases, and 
* Classical and Koine Greek text in Unicode. 

Κοινός means 'common' in Ancient Greek. This package contains a number of
reusable services and utilities from the [Hexapla][hex] project. 

[hex]: http://hexap.la 

## Installation 

You will need PHP >=5.4 and phpunit >=4.0.

In `composer.json`: 

```bash
"require": {
    "eukras/koinos": "~1.1"
}
```

## Tests 

The tests are the best documentation for developers. 

```bash
composer dump-autoload   # updates /vendor for autoloading; see .gitignore
phpunit -c tests
```

## Library data files 

```bash
./src/Resources/library/lxx/books.csv
./src/Resources/library/nt/books.csv
```

In each file the CSV lines specify id, library-name, name, short-name, handle,
reference-depth, aliases, total-chapters. 

* Handles are the short names used in URIs, e.g. `Romans 1` has handle `rom+1`.
* Normal books like 1 Corinthians are reference depth 2 (chapter:verse), but
single-chapter books like Philemon are identified by verse only, so have a
reference depth of 1.  

```csv
...
107,NT,1 Corinthians,1 Cor,1cor,2,1co,16
...
118,NT,Philemon,Phm,phm,1,phl/philem,1
...
```

## Service\ReferenceManager and Utility\Reference

The ReferenceManager is use to construct, manipulate and format References. 

```bash
./src/Utility/Reference.php
./src/Service/ReferenceManager.php
./tests/Utility/Reference.php
./tests/Service/ReferenceManager.php
```

### Examples

Initialise the ReferenceManager as follows: 

```php
use Koinos\Service\ReferenceManager;
$rm = new ReferenceManager($libraries=['nt', 'lxx']);
```

Libraries are easy to create: just add `Resources/library/$library/books.csv`
(see above), and pass `$library` as a constructor argument.

The ReferenceManager handles most Reference operations. 

```php
$mattId = $rm->matchBookName('matt');  //  returns (int)101, say.
$matt28 = $rm->createReferenceFromBookAndChapter($mattId, 28); 
```

`$matt28` is now a `Reference` object. 

```php
$mark1 = $rm->getNextChapterReference($matt28); 

echo $rm->getShortTitle($matt28);  //  Matt 28
echo $rm->getHandle($matt28);      //  matt+28
```

You will normally want to work with complex human-readable reference strings: 

```php
$ref1 = $rm->createReferenceFromQuery('1 Cor 16:1-5,8,10-12,13-14');
$ref2 = $rm->createReferenceFromQuery('1cor+16.1-4,5,8,10-14');
```

In this example `$ref1` generates identically to `$ref2`. Adjacent verse ranges
are combined together into a standard reference. Koinos does not know or care
how many verses are in a given chapter, though; internally, full-chapter links 
are treated as Book 1:1-999. 

```php
echo $rm->getTitle($ref1);  //  1 Corinthians 16:1-5,8,10-14 
echo $rm->getTitle($ref2);  //  1 Corinthians 16:1-5,8,10-14

$ref1->equals($ref2);       //  true
$ref1->contains($ref2);     //  true
```

All this together allows HTML links to be generated easily, though in a
framework you would most likely plug `$rm->getHandle($ref)` into a route. 

```php
echo $rm->getLink($matt28, $uriPrefix='/r/');
```

Being able to generate links allows them to be auto-replaced in text: 

```php
$linkedHtml = $rm->linkReferencesInHtml($textContainingReferences, '/r/'); 
```

The scanner for matching links in text will identify if they exist in
parallels, that is, if references are separated by a pipe character, e.g.  "The
Psalms Ps 14 | Ps 53 are the same." See the test suite for examples. 

### Utility\Reference Internals

Internal working is done with quadruples of `[book, section, chapter, verse]`;
In `$ref1`, 1cor is book #107, and has two-level referencing (c:v), so the
section number is always 1. Every quadruple becomes an `UNSIGNED INT(12)` for
SQL (see below). 

Query      | Quadruple       | Index 
---------- | --------------- | ------------
1 Cor 16:1 | [107, 1, 16, 1] | 107001016001

This makes it easy to efficiently index and match references and ranges: 

```php
echo $ref1->getSqlClause($columnName='reference'); 

//  (reference BETWEEN 107001016001 AND 107001016005) OR 
//  (reference BETWEEN 107001016008 AND 107001016008) OR 
//  (reference BETWEEN 107001016010 AND 107001016014)  

echo $ref1->getSqlRangeClause($startColumn='rangeBegins', $endColumn='rangeEnds'); 

//  (rangeEnds >= 107001016001 AND rangeBegins <= 107001016005) OR 
//  (rangeEnds >= 107001016008 AND rangeBegins <= 107001016008) OR 
//  (rangeEnds >= 107001016010 AND rangeBegins <= 107001016014)  
```

The only gotcha is that, in PHP, these numbers must be treated as a strings, or
cast to `(double)`: 12 digits exceed PHP's integer length. References are
usually manipulated as quadruples though, which are regular integers 1-999. 

## Utility\Greek

The Greek utility performs simple manipulation and scanning of Greek text: 

```bash
./src/Utility/Greek.php
./tests/Utility/Greek.php 
```

Initialise without arguments: 

```php
use Koinos\Utility\Greek;  //  see composer.json
$g = new Greek;
```

This performs romanization, and offers some Unicode convenience wrappers. 

```php
echo $g->romanize('Ῥύγχος');   //  Rhunchos
echo $g->romanize('Ἡσυχάζω');  //  Hēsuchazō
echo $g->romanize('Αὑτοῖσι');  //  Hautoisi

echo $g->length('ᾁ');          //  1
echo $g->lowercase('Α');       //  α
echo $g->unicodeChr('1f0c');   //  Ἄ
```

Here's how it would be used to scan the structure of Psalm 116 (LXX) -- in
Hexapla this is used to save texts word-by-word into a database. 

```php
$ps116 = "1 αλληλουια.

αἰνεῖτε τὸν κύριον πάντα τὰ ἔθνη. ἐπαινέσατε αὐτόν πάντες οἱ λαοί.

2 ὅτι ἐκραταιώθη τὸ ἔλεος αὐτοῦ [ἐφ’ ἡμᾶς] καὶ ἡ ἀλήθεια τοῦ κυρίου
μένει εἰς τὸν αἰῶνα.

Τί εἰς τέλος;";
```

For each word, let's grab its book/chapter/verse numbers (BCV), then
paragraph/sentence/word numbers (PSW), and then any leading or trailing
punctuation. (Psalms are book 226.) 

```php
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
```

See the class and Test suites for more. 
