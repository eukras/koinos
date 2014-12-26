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

use Koinos\Bundle\KoinosBundle\Utility\Greek; 

class GreekTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    { 
       $this->g = new Greek;  
    } 

    /*
     * Basics 
     * ------------------------------------------------------------
     *
     */

    public function testLowercase()
    {
        $this->assertEquals(
            'αβγδεζηθικλμνξοπρστυφψω', 
            $this->g->lowercase('ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΠΡΣΤΥΦΨΩ') 
        ); 
    }

    public function testLength()
    {
        $this->assertEquals(
            1, $this->g->length('ᾁ')
            ); 
        $this->assertEquals(
            23, $this->g->length('αβγδεζηθικλμνξοπρστυφψω')
        ); 
    }

    public function testUnicodeChr() 
    {
        $this->assertEquals(
            'Ἄ', $this->g->unicodeChr('1f0c')
        ); 
    }

    /*
     * Filtering 
     * ------------------------------------------------------------
     *
     */

    /**
     * High level test. This will check stripAccents, stripBreathings and a lot
     * more. 
     *
     * Note: Perseus data contained nonsense like: 
     * Ἀγαμεμνόνιος: chagachmemnonios 
     *
     */
    public function testRomanization()
    {
        $map = array( 
            "'γκαλῇ" => "'nkalē", 
            'Αὕτη' => 'Hautē', 
            'αὕτη' => 'hautē', 
            'Αὗται' => 'Hautai', 
            'αὗται' => 'hautai', 
            'Εἱλώτων' => 'Heilōtōn', 
            'εἱλώτων' => 'heilōtōn', 
            'Εἵλωσι' => 'Heilōsi', 
            'εἵλωσι' => 'heilōsi', 
            'Εἷσαν' => 'Heisan', 
            'εἷσαν' => 'heisan', 
            'Εὕρισκε' => 'Heuriske', 
            'εὕρισκε' => 'heuriske', 
            'Κακοΐλιος' => 'Kakoilios', 
            'κακοΐλιος' => 'kakoilios', 
            'Ξυνενέγκοι' => 'Xunenenkoi', 
            'ξυνενέγκοι' => 'xunenenkoi', 
            'Οἵδε' => 'Hoide', 
            'οἵδε' => 'hoide', 
            'Οἷα' => 'Hoia', 
            'οἷα' => 'hoia', 
            'Οὗτος' => 'Houtos', 
            'οὗτος' => 'houtos', 
            'Υἱέοιν' => 'Huieoin', 
            'υἱέοιν' => 'huieoin', 
            'Φοῖνιξ' => 'Phoinix', 
            'φοῖνιξ' => 'phoinix', 
            'Αὑτοῖσι' => 'Hautoisi', 
            'αὑτοῖσι' => 'hautoisi', 
            'Τύγχαν' => 'Tunchan', 
            'τύγχαν' => 'tunchan', 
            'Ψευδέγγραφος' => 'Pseudengraphos', 
            'ψευδέγγραφος' => 'pseudengraphos', 
            'Ἄγκυρα' => 'Ankura', 
            'ἄγκυρα' => 'ankura', 
            'Ἑλληνικῆς' => 'Hellēnikēs', 
            'ἑλληνικῆς' => 'hellēnikēs', 
            'Ἔλεγχος' => 'Elenchos', 
            'ἔλεγχος' => 'elenchos', 
            'Ἡσυχάζω' => 'Hēsuchazō', 
            'ἡσυχάζω' => 'hēsuchazō', 
            'Ἧβα' => 'Hēba', 
            'ἧβα' => 'hēba', 
            'Ἧιχι' => 'Hēichi', 
            'ἧιχι' => 'hēichi', 
            'Ἰησους' => 'Iēsous', 
            'ἰησους' => 'iēsous', 
            '὏κως' => 'Hokōs', // <-- Non-combining char in Perseus dataset. 
            'Ὑακίνθια' => 'Huakinthia', 
            'ὑακίνθια' => 'huakinthia', 
            'Ὑπέφευγον' => 'Hupepheugon', 
            'ὑπέφευγον' => 'hupepheugon', 
            'Ὕβρεως' => 'Hubreōs', 
            'ὕβρεως' => 'hubreōs', 
            'Ὗλαι' => 'Hulai', 
            'ὗλαι' => 'hulai', 
            'Ὡμοιώθη' => 'Hōmoiōthē', 
            'ὡμοιώθη' => 'hōmoiōthē', 
            'Ὧσπερ' => 'Hōsper', 
            'ὧσπερ' => 'hōsper', 
            'Ῥύγχος' => 'Rhunchos', 
            'ῥύγχος' => 'rhunchos', 
            ); 
        foreach ($map as $greek => $roman) { 
            $this->assertEquals($roman, $this->g->romanize($greek)); 
        }

    }

    /*
     * Splitters
     * ------------------------------------------------------------
     *
     */

    public function testCountGreekWords()
    {
        $sixWords = "τῇ ἐκκλησίᾳ τοῦ θεοῦ τῇ παροικούσῃ.";
        $this->assertEquals(
            6, 
            $this->g->countGreekWords($sixWords)
        );
    }

    public function testMatchWordsAndReferenceNumbers()
    {
        $s = "[ὁ] ἐν τῷ 49 οὐρανῷ, καὶ ὤφθη ἡ κιβωτὸς τῆς " 
           . "διαθήκης αὐτοῦ ἐν τῷ ναῷ αὐτοῦ· "; 

        $match = $this->g->matchWordsAndReferenceNumbers($s);

        $this->assertEquals(16, count($match)); 

        $ho     = $match[1]; 
        $n49    = $match[4]; 
        $ourano = $match[5]; 
        $autou  = $match[16]; 

        $this->assertEquals(3, count($ho)); 
        $this->assertEquals(3, count($n49)); 
        $this->assertEquals(3, count($ourano)); 
        $this->assertEquals(3, count($autou)); 

        list($pre1, $w1, $suf1) = $ho;
        list($pre2, $w2, $suf2) = $n49;
        list($pre3, $w3, $suf3) = $ourano;
        list($pre4, $w4, $suf4) = $autou;

        $this->assertEquals('[', $pre1); 
        $this->assertEquals('ὁ', $w1); 
        $this->assertEquals(']', $suf1); 

        $this->assertEquals('', $pre2); 
        $this->assertEquals('49', $w2); 
        $this->assertEquals('', $suf2 ); 

        $this->assertEquals('', $pre3); 
        $this->assertEquals('οὐρανῷ', $w3); 
        $this->assertEquals(',', $suf3); 

        $this->assertEquals('', $pre4); 
        $this->assertEquals('αὐτοῦ', $w4); 
        $this->assertEquals('·', $suf4); 
        
    }

    public function testSplitParagraphs()
    { 
        $this->assertEquals(
            3, count($this->g->splitParagraphs("1\n\n2\n\n3"))
            ); 
        $this->assertEquals(
            3, count($this->g->splitParagraphs("1\r\r2\r\r3"))
            ); 
        $this->assertEquals(
            3, count($this->g->splitParagraphs("1\r\n\r\n2\r\n\r\n3"))
            ); 
        $this->assertEquals(
            3, count($this->g->splitParagraphs("1\n\r\n\r2\n\r\n\r3"))
            ); 
        $this->assertEquals(
            3, count($this->g->splitParagraphs("1

2

3"))
            ); 
    }

    public function testGetParagraphsAndSentences() 
    {
        $rev11 = "
1 Καὶ ἐδόθη μοι κάλαμος ὅμοιος ῥάβδῳ, λέγων, Ἔγειρε καὶ μέτρησον τὸν ναὸν τοῦ
θεοῦ καὶ τὸ θυσιαστήριον καὶ τοὺς προσκυνοῦντας ἐν αὐτῷ. 2 καὶ τὴν αὐλὴν τὴν
ἔξωθεν τοῦ ναοῦ ἔκβαλε ἔξωθεν καὶ μὴ αὐτὴν μετρήσῃς, ὅτι ἐδόθη τοῖς ἔθνεσιν,
καὶ τὴν πόλιν τὴν ἁγίαν πατήσουσιν μῆνας τεσσαράκοντα δύο. 3 καὶ δώσω τοῖς
δυσὶν μάρτυσίν μου, καὶ προφητεύσουσιν ἡμέρας χιλίας διακοσίας ἑξήκοντα
περιβεβλημένοι σάκκους. 4 οὗτοί εἰσιν αἱ δύο ἐλαῖαι καὶ αἱ δύο λυχνίαι αἱ
ἐνώπιον τοῦ κυρίου τῆς γῆς ἑστῶτες. 5 καὶ εἴ τις αὐτοὺς θέλει ἀδικῆσαι, πῦρ
ἐκπορεύεται ἐκ τοῦ στόματος αὐτῶν καὶ κατεσθίει τοὺς ἐχθροὺς αὐτῶν· καὶ εἴ τις
θελήσει αὐτοὺς ἀδικῆσαι, οὕτως δεῖ αὐτὸν ἀποκτανθῆναι. 6 οὗτοι ἔχουσιν τὴν
ἐξουσίαν κλεῖσαι τὸν οὐρανόν, ἵνα μὴ ὑετὸς βρέχῃ τὰς ἡμέρας τῆς προφητείας
αὐτῶν, καὶ ἐξουσίαν ἔχουσιν ἐπὶ τῶν ὑδάτων στρέφειν αὐτὰ εἰς αἷμα καὶ πατάξαι
τὴν γῆν ἐν πάσῃ πληγῇ ὁσάκις ἐὰν θελήσωσιν. 7 καὶ ὅταν τελέσωσιν τὴν μαρτυρίαν
αὐτῶν, τὸ θηρίον τὸ ἀναβαῖνον ἐκ τῆς ἀβύσσου ποιήσει μετ’ αὐτῶν πόλεμον καὶ
νικήσει αὐτοὺς καὶ ἀποκτενεῖ αὐτούς. 8 καὶ τὸ πτῶμα αὐτῶν ἐπὶ τῆς πλατείας τῆς
πόλεως τῆς μεγάλης, ἥτις καλεῖται πνευματικῶς Σόδομα καὶ Αἴγυπτος, ὅπου καὶ ὁ
κύριος αὐτῶν ἐσταυρώθη. 9 καὶ βλέπουσιν ἐκ τῶν λαῶν καὶ φυλῶν καὶ γλωσσῶν καὶ
ἐθνῶν τὸ πτῶμα αὐτῶν ἡμέρας τρεῖς καὶ ἥμισυ, καὶ τὰ πτώματα αὐτῶν οὐκ ἀφίουσιν
τεθῆναι εἰς μνῆμα. 10 καὶ οἱ κατοικοῦντες ἐπὶ τῆς γῆς χαίρουσιν ἐπ’ αὐτοῖς καὶ
εὐφραίνονται, καὶ δῶρα πέμψουσιν ἀλλήλοις, ὅτι οὗτοι οἱ δύο προφῆται ἐβασάνισαν
τοὺς κατοικοῦντας ἐπὶ τῆς γῆς. 11 καὶ μετὰ τὰς τρεῖς ἡμέρας καὶ ἥμισυ πνεῦμα
ζωῆς ἐκ τοῦ θεοῦ εἰσῆλθεν ἐν αὐτοῖς, καὶ ἔστησαν ἐπὶ τοὺς πόδας αὐτῶν, καὶ
φόβος μέγας ἐπέπεσεν ἐπὶ τοὺς θεωροῦντας αὐτούς. 12 καὶ ἤκουσαν φωνῆς μεγάλης
ἐκ τοῦ οὐρανοῦ λεγούσης αὐτοῖς, Ἀνάβατε ὧδε· καὶ ἀνέβησαν εἰς τὸν οὐρανὸν ἐν τῇ
νεφέλῃ, καὶ ἐθεώρησαν αὐτοὺς οἱ ἐχθροὶ αὐτῶν. 13 Καὶ ἐν ἐκείνῃ τῇ ὥρᾳ ἐγένετο
σεισμὸς μέγας, καὶ τὸ δέκατον τῆς πόλεως ἔπεσεν, καὶ ἀπεκτάνθησαν ἐν τῷ σεισμῷ
ὀνόματα ἀνθρώπων χιλιάδες ἑπτά, καὶ οἱ λοιποὶ ἔμφοβοι ἐγένοντο καὶ ἔδωκαν δόξαν
τῷ θεῷ τοῦ οὐρανοῦ. 14 Ἡ οὐαὶ ἡ δευτέρα ἀπῆλθεν· ἰδοὺ ἡ οὐαὶ ἡ τρίτη ἔρχεται
ταχύ. 15 Καὶ ὁ ἕβδομος ἄγγελος ἐσάλπισεν· καὶ ἐγένοντο φωναὶ μεγάλαι ἐν τῷ
οὐρανῷ λέγοντες, Ἐγένετο ἡ βασιλεία τοῦ κόσμου τοῦ κυρίου ἡμῶν καὶ τοῦ Χριστοῦ
αὐτοῦ, καὶ βασιλεύσει εἰς τοὺς αἰῶνας τῶν αἰώνων. 16 καὶ οἱ εἴκοσι τέσσαρες
πρεσβύτεροι οἳ ἐνώπιον τοῦ θεοῦ κάθηνται ἐπὶ τοὺς θρόνους αὐτῶν ἔπεσαν ἐπὶ τὰ
πρόσωπα αὐτῶν καὶ προσεκύνησαν τῷ θεῷ 17 λέγοντες, Εὐχαριστοῦμέν σοι, κύριε ὁ
θεὸς ὁ παντοκράτωρ, ὁ ὢν καὶ ὁ ἦν, ὅτι εἴληφας τὴν δύναμίν σου τὴν μεγάλην καὶ
ἐβασίλευσας· 18 καὶ τὰ ἔθνη ὠργίσθησαν, καὶ ἦλθεν ἡ ὀργή σου καὶ ὁ καιρὸς τῶν
νεκρῶν κριθῆναι καὶ δοῦναι τὸν μισθὸν τοῖς δούλοις σου τοῖς προφήταις καὶ τοῖς
ἁγίοις καὶ τοῖς φοβουμένοις τὸ ὄνομά σου, τοὺς μικροὺς καὶ τοὺς μεγάλους, καὶ
διαφθεῖραι τοὺς διαφθείροντας τὴν γῆν. 19 καὶ ἠνοίγη ὁ ναὸς τοῦ θεοῦ [ὁ] ἐν τῷ
οὐρανῷ, καὶ ὤφθη ἡ κιβωτὸς τῆς διαθήκης αὐτοῦ ἐν τῷ ναῷ αὐτοῦ· καὶ ἐγένοντο
ἀστραπαὶ καὶ φωναὶ καὶ βρονταὶ καὶ σεισμὸς καὶ χάλαζα μεγάλη.
        "; 

        $paragraphsAndSentences = $this->g->getParagraphsAndSentences($rev11); 
        $this->assertEquals(1, count($paragraphsAndSentences)); 

        $paragraph1 = reset($paragraphsAndSentences); 
        $this->assertEquals(17, count($paragraph1)); 

        $sentence1 = reset($paragraph1);  //  String 
        $this->assertTrue(is_string($sentence1)); 

    }

    public function testGetStructure()
    {

        //  Note invisible spaces on lines 2 and 4...

        $ps116 = "1 αλληλουια. 
             
αἰνεῖτε τὸν κύριον πάντα τὰ ἔθνη. ἐπαινέσατε αὐτόν πάντες οἱ λαοί.
          
2 ὅτι ἐκραταιώθη τὸ ἔλεος αὐτοῦ [ἐφ’ ἡμᾶς] καὶ ἡ ἀλήθεια τοῦ κυρίου μένει εἰς
τὸν αἰῶνα.

Τί εἰς τέλος; 
"; 

        $structure = $this->g->getStructure($ps116, 226, 116);

        $this->assertEquals([

            //    b    c    v  p  s   w  prefix  word      suffix 
            //    :    :    :  :  :   :  :       :         : 
                [ 226, 116, 1, 1, 1,  1, '', 'αλληλουια',  '.' ], 
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
                [ 226, 116, 2, 4, 1,  3, '', 'τέλος',      ';' ], 

            ], $structure
        ); 
    }
}
