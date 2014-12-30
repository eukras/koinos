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
 * Greek
 *
 * Utilities for working with Greek Unicode strings.  
 * 
 * ---------
 * Use Cases 
 * ---------
 *
 * 1) Romanize Greek strings. 
 * 2) Extract paragraphs, sentences and words from Greek texts. 
 *
 * -----
 * Notes
 * -----
 *
 * - PHP's multibyte support has relied on mb_* functions
 * - Unicode has duplicates for identical Greek letters. 
 *
 * ------
 * Future 
 * ------
 *
 * - Should merge non-combining accents into precomposed forms:
 *
 *      1FEF Grave
 *      1FFD Acute
 *      1FC0 Circumflex
 *      1FBF Smooth Breathing
 *      1FFE Rough Breathing
 *      1FCD Smooth breathing + grave
 *      1FDD Rough breathing + grave
 *      1FCE Smooth breathing + acute
 *      1FDE Rough breathing + acute
 *      1FCF Smooth breathing + circumflex
 *      1FDF Rough breathing + circumflex
 *      1FBE Iota subscript
 *      1FED Diaeresis + grave
 *      1FEE Diaeresis + acute
 *      1FC1 Diaeresis +circumflex
 *
 */

class Greek
{


    /*
     * Basics, incl. wrap multibyte functions. 
     * ------------------------------------------------------------
     */

    /**
     * Create a character. 
     *
     * @todo Again, more efficiently. 
     * 
     * @param string $hex 
     * @access public
     * @return string
     */
    public function unicodeChr($hex) 
    { 
        return mb_convert_encoding("&#x$hex;", 'UTF-8', 'HTML-ENTITIES');
    }

    /*
     * Unicode lowercase conversion. 
     *
     * @param mixed $str 
     * @access public
     * @return void
     */
    public function lowercase($str)
    {
        return mb_strtolower($str, 'UTF-8'); 
    }

    /**
     * Unicode string length. 
     * 
     * @param string $s 
     * @return integer
     */
    public function length($s)
    { 
        return mb_strlen($s, 'UTF-8');
    }

    /**
     * Return an array of the unicode characters in a string.
     *
     * @todo This is inefficient. Str_split may now be multibyte. 
     *  
     * @param string $string 
     * @return string 
     */
    function getCharacters($s)
    { 
        $strlen = mb_strlen($s); 
        while ($strlen > 0) { 
            $array[] = mb_substr($s, 0, 1, "UTF-8"); 
            $s = mb_substr($s, 1, $strlen, "UTF-8"); 
            $strlen = mb_strlen($s); 
        } 
        return $array; 
    } 

    public function stringReverse($s)
    {
        return join('', array_reverse($this->getCharacters($s))); 
    }

    /*
     * Filtering
     * ------------------------------------------------------------
     */

    /*
     * Filter Greek text coming in to the system. 
     *
     *
     */
    public function filter($str)
    {
        return $this->fixDuplicateCharacters($str); 
    }

    /**
     * Unicode contains duplicates for vowels with acute accents; standardize
     * these to allow simple comparison. 
     * 
     * @param mixed $str 
     * @access public
     * @return void
     */
    public function fixDuplicateCharacters($str)
    {
        //  REPLACE(_, 'ά', 'ά') - 1F71,03AC - Lowercase Alpha + acute
        //  REPLACE(_, 'Ά', 'Ά') - 1FBB,0386 - Uppercase Alpha + acute
        //  REPLACE(_, 'έ', 'έ') - 1F73,03AD - Lowercase Epsilon + acute
        //  REPLACE(_, 'Έ', 'Έ') - 1FC9,0388 - Uppercase Epsilon + acute
        //  REPLACE(_, 'ή', 'ή') - 1F75,03AE - Lowercase Eta + acute
        //  REPLACE(_, 'Ή', 'Ή') - 1FCB,0389 - Uppercase Eta + acute
        //  REPLACE(_, 'ί', 'ί') - 1F77,03AF - Lowercase Iota + acute
        //  REPLACE(_, 'Ί', 'Ί') - 1FDB,038A - Uppercase Iota + acute
        //  REPLACE(_, 'ό', 'ό') - 1F79,03CC - Lowercase Omicron + acute
        //  REPLACE(_, 'Ό', 'Ό') - 1FF9,038C - Uppercase Omicron + acute
        //  REPLACE(_, 'ύ', 'ύ') - 1F7B,03CD - Lowercase Upsilon + acute
        //  REPLACE(_, 'Ύ', 'Ύ') - 1FEB,038E - Uppercase Upsilon + acute
        //  REPLACE(_, 'ώ', 'ώ') - 1F7D,03CE - Lowercase Omega + acute
        //  REPLACE(_, 'Ώ', 'Ώ') - 1FFB,038F - Uppercase Omega + acute    

        //  Extra case: <὏> 8015, Hex 1f4f, Octal 17517 
        //  1FBF is a non-combining circumflex, hence the display issues, but
        //  it appears in the Perseus dataset. It does not exist as a Unicode
        //  Extended Greek character. Leaving in the DB. Romanizing to "ho".   
        //
        //  <὏> 8015, Hex 1f4f, Octal 17517

        $tr = array(

            $this->unicodeChr('1F71') => $this->unicodeChr('03AC'),   //  Lowercase Alpha + acute
            $this->unicodeChr('1FBB') => $this->unicodeChr('0386'),   //  Uppercase Alpha + acute
            $this->unicodeChr('1F73') => $this->unicodeChr('03AD'),   //  Lowercase Epsilon + acute
            $this->unicodeChr('1FC9') => $this->unicodeChr('0388'),   //  Uppercase Epsilon + acute
            $this->unicodeChr('1F75') => $this->unicodeChr('03AE'),   //  Lowercase Eta + acute
            $this->unicodeChr('1FCB') => $this->unicodeChr('0389'),   //  Uppercase Eta + acute
            $this->unicodeChr('1F77') => $this->unicodeChr('03AF'),   //  Lowercase Iota + acute
            $this->unicodeChr('1FDB') => $this->unicodeChr('038A'),   //  Uppercase Iota + acute
            $this->unicodeChr('1F79') => $this->unicodeChr('03CC'),   //  Lowercase Omicron + acute
            $this->unicodeChr('1FF9') => $this->unicodeChr('038C'),   //  Uppercase Omicron + acute
            $this->unicodeChr('1F7B') => $this->unicodeChr('03CD'),   //  Lowercase Upsilon + acute
            $this->unicodeChr('1FEB') => $this->unicodeChr('038E'),   //  Uppercase Upsilon + acute
            $this->unicodeChr('1F7D') => $this->unicodeChr('03CE'),   //  Lowercase Omega + acute
            $this->unicodeChr('1FFB') => $this->unicodeChr('038F'),   //  Uppercase Omega + acute    

            $this->unicodeChr('2019') => "'",                        //  Abbreviation in classical texts: ’
            $this->unicodeChr('1FBD') => "'",                        //  Koronis is given as a single-quote in the DB.

            "’" => "'",     //  Abbreviation was unmatched -- ? 

            ); 

        return str_replace(
            array_keys($tr), 
            array_values($tr), 
            $str
        );
    }

    /**
     * Standardize characters... 
     *
     * @param string|array $str 
     *
     */
    public function standardizeCharacters($str)
    {
        if (is_array($str)) { 
            $new = [];
            foreach ($str as $key => $value) { 
                $new[$key] = $this->standardizeCharacters($value);
            }
            return $new; 
        } else { 
            return $this->fixDuplicateCharacters($str); 
        }
    }

    /*
     * Matching 
     * ------------------------------------------------------------
     *
     */

    /**
     * A single authoritative source for the regexp that detects Greek words. 
     *
     * Javascript would be:  
     * '/([\u0370-\u037D\u0386-\u0386\u0388-\u03FF\u1F00-\u1FFF])+/u';  
     * 
     * @access public
     * @return void
     */
    public function regexp()
    {
        return '/['
             . "'?"  //  single-quote for abbrevs
             . '\x{0370}-\x{037D}' 
             . '\x{0386}-\x{0386}' 
             . '\x{0388}-\x{03FF}' 
             . '\x{1F00}-\x{1FFF}'
             . ']+'
             . "'?"  //  single-quote for abbrevs
             . '/u'  //  unicode
             ; 
    }

    /**
     * Variation on $this->regexp(), so keep in sync; also grabs numbers for
     * indexing. 
     * 
     * @param string $str 
     * @access public
     * @return array([prePunctuation, word, postPunctuation])
     */
    public function matchWordsAndReferenceNumbers($str) 
    {
        //  Two kinds of ';'
        $punctuation = preg_quote('[]()";;·,ͺ.-'); 
        $regexp = "/([$punctuation]*)([\x{0370}-\x{037D}\x{0386}-\x{0386}\x{0388}-\x{03FF}\x{1F00}-\x{1FFF}]+'?’?|\d+)([$punctuation]*)/u"; 
        if (preg_match_all($regexp, $str, $matches) > 0) { 
            $triples = []; 
            foreach ($matches[0] as $i => $whatever) { 
                $triples[] = array($matches[1][$i], $matches[2][$i], $matches[3][$i]); 
            }
            return $this->renumberArray($triples); 
        } else { 
            return []; 
        }
    }

    /**
     * Simple word counter. 
     * 
     * @param mixed $str 
     * @access public
     * @return void
     */
    public function countGreekWords($str)
    {
        return preg_match_all($this->regexp(), $str, $matches); 
    }


    /**
     * Simple way to check if there is any Greek text
     * 
     * @param string $str 
     * @access public
     * @return boolean
     */
    public function containsGreekText($str)
    {
        return preg_match($this->regexp(), $str) > 0; 
    }

    /**
     * Return an array of unicode words from a string
     *
     * 0370-03FF = Greek and Coptic 
     * 1F00-1FFF = Greek extended 
     *
     * We do NOT accept punctuation as part of a word. 
     *
     * @note REMEMBER to standardizeCharacters! 
     *
     * @see unicode.org/charts/PDF/U0370.pdf
     * @see unicode.org/charts/PDF/U1F00.pdf
     * 
     * @param string $str 
     * @access public
     * @return array
     */
    public function matchWords($str) 
    {
        $unicode = $this->regexp(); 
        if (preg_match_all($unicode, $str, $matches) > 0) {    
            return $matches[0]; 
        } else { 
            return []; 
        }

    }

    /**
     * Perform a replace that only acts on COMPLETE words. 
     * 
     * @param mixed $swapArray 
     * @param mixed $withArray 
     * @param mixed $str 
     * @access public
     * @return void
     */
    public function wordReplace($swapArray, $withArray, $str)
    {
        $pregSwapArray = []; 

        foreach ($swapArray as $key => $value) { 

            //  Fiddliness... 
            //  Match a word boundary unless bounded by an apostrophe. 
            //
            $regex  = '/';
            $regex .= (substr($value, 0, 1) == "'") ? '' : '\b'; 
            $regex .= preg_quote($value, '/'); 
            $regex .= (substr($value, -1) == "'") ? '' : '\b'; 
            $regex .= '/u'; 

            $pregSwapArray[] = $regex; 
        }

        return preg_replace($pregSwapArray, $withArray, $str); 
    }

    /**
     * 
     * 
     * @param mixed $string 
     * @param string $plusChars 
     * @access public
     * @return void
     */
    public function stripUnicode($string, $plusChars='')
    {
        $new = ''; 
        for ($i = 0; $i < strlen ($string); $i++) { 
            $c = $string[$i]; 
            if (ord($c) < 128) { 
                if (strpos($plusChars, $c) === false) { 
                    $new .= $string[$i];
                }
            }
        }
        return $new;
    }

    public function stripNonUnicode($string)
    {
        return preg_replace($this->regexp(), '\0', $string); 
    }

    /**
     * Unicode-aware string trim... 
     *
     */
    public function trimLength
        ($text, $len=250, $ellipsis='...', $splitWords=true)
    {
        // clips string to last space before $len chars, adds ellipsis
        if (mb_strlen($text) > $len) {
            $clipLength = $len - mb_strlen($ellipsis);
            if ($clipLength < 1) {
                return $text;
            } else {
                $text = mb_substr($text, 0, $clipLength);
                $last = mb_strrpos($text, ' ');
                if (($last == false) and ($splitWords == true)) {
                    return mb_substr($text, 0, $clipLength).$ellipsis;
                } else {
                    return mb_substr($text, 0, $last).$ellipsis;
                }
            }
        } else {
            return $text;
        }
    }

    /**
     * Could be used in an editor. 
     * 
     * @access public
     * @return array
     */
    public function getCriticalMarks()
    {
        return array(
            $this->unicodeChr('005B') => 'Authenticity questioned (begins)', 
            $this->unicodeChr('005D') => 'Authenticity questioned (end)', 
            $this->unicodeChr('002A') => 'Original reading when a correction has been made', 
            $this->unicodeChr('007C') => 'Instance of variation separation within a verse', 
            $this->unicodeChr('00A6') => 'Alternative readings within an instance of a variation', 
            $this->unicodeChr('00B0') => 'One-word omission', 
            $this->unicodeChr('0085') => 'Text of edition same as variant', 
            $this->unicodeChr('2020') => 'Mutilated codex', 
            $this->unicodeChr('2022') => 'Apparatus Section', 
            $this->unicodeChr('271D') => 'Change in NA from NA27', 
            $this->unicodeChr('27E6') => 'Early Addition, Authennticity denied (begins)', 
            $this->unicodeChr('27E7') => 'Early Addition, Authennticity denied (ends)', 
            $this->unicodeChr('2E00') => 'Following word repeated', 
            $this->unicodeChr('2E01') => 'Following word replaced (recurrence)', 
            $this->unicodeChr('2E02') => 'Replacement (begins)', 
            $this->unicodeChr('2E03') => 'Replacement (ends)', 
            $this->unicodeChr('2E04') => 'Replacement recurrence (begins)', 
            $this->unicodeChr('2E05') => 'Replacement recurrence (ends)', 
            $this->unicodeChr('2E06') => 'One or more words inserted', 
            $this->unicodeChr('2E07') => 'Insertion recurrence', 
            $this->unicodeChr('2E08') => 'Transposition as indicated in apparatus', 
            $this->unicodeChr('2E09') => 'Transposition in quoted witnesses (begins)', 
            $this->unicodeChr('2E0A') => 'Transposition in quoted witnesses (ends)', 
            $this->unicodeChr('2E0B') => 'Omission (begins)', 
            $this->unicodeChr('0192') => 'Family', 
            $this->unicodeChr('210C') => 'Hebrew Bible', 
            $this->unicodeChr('2135') => 'Codex Sinaiticus', 
            $this->unicodeChr('03DB') => 'Stephanus in Eusebius&rsquo; letter to Carpian', 
            $this->unicodeChr('1D50A') => 'Septuagint (PUA E316 in SBL BibLit)', 
            $this->unicodeChr('1D459') => 'Lectionary', 
            $this->unicodeChr('1D510') => 'Majority Text', 
            $this->unicodeChr('1D513') => 'Papyrus', 
            ); 
    }

    /**
     * Normalize words by removing accents 
     * 
     * @param string $str 
     * @access public
     * @return string
     */
    public function stripAccents($str) 
    {
        $tr = array(
            // Basic set 
            'Ά' => 'Α', 
            'Έ' => 'Ε', 
            'Ή' => 'Η', 
            'Ί' => 'Ι', 
            'Ό' => 'Ο', 
            'Ύ' => 'Υ', 
            'Ώ' => 'Ω', 
            'ΐ' => 'ι', 
            //  Extended 
            'ἀ' => 'ἀ', 
            'ἁ' => 'ἁ', 
            'ἂ' => 'ἀ', 
            'ἃ' => 'ἁ', 
            'ἄ' => 'ἀ', 
            'ἅ' => 'ἁ', 
            'ἆ' => 'ἀ', 
            'ἇ' => 'ἁ', 
            'Ἀ' => 'Ἀ', 
            'Ἁ' => 'Ἁ', 
            'Ἂ' => 'Ἀ', 
            'Ἃ' => 'Ἁ', 
            'Ἄ' => 'Ἀ', 
            'Ἅ' => 'Ἁ', 
            'Ἆ' => 'Ἀ', 
            'Ἇ' => 'Ἁ', 
            'ἐ' => 'ἐ', 
            'ἑ' => 'ἑ', 
            'ἒ' => 'ἐ', 
            'ἓ' => 'ἑ', 
            'ἔ' => 'ἐ', 
            'ἕ' => 'ἑ', 
            'Ἐ' => 'Ἐ', 
            'Ἑ' => 'Ἑ', 
            'Ἒ' => 'Ἐ', 
            'Ἓ' => 'Ἑ', 
            'Ἔ' => 'Ἐ', 
            'Ἕ' => 'Ἑ', 
            'ἠ' => 'ἠ', 
            'ἡ' => 'ἡ', 
            'ἢ' => 'ἠ', 
            'ἣ' => 'ἡ', 
            'ἤ' => 'ἠ', 
            'ἥ' => 'ἡ', 
            'ἦ' => 'ἠ', 
            'ἧ' => 'ἡ', 
            'Ἠ' => 'Ἠ', 
            'Ἡ' => 'Ἡ', 
            'Ἢ' => 'Ἠ', 
            'Ἣ' => 'Ἡ', 
            'Ἤ' => 'Ἠ', 
            'Ἥ' => 'Ἡ', 
            'Ἦ' => 'Ἠ', 
            'Ἧ' => 'Ἡ', 
            'ἰ' => 'ἰ', 
            'ἱ' => 'ἱ', 
            'ἲ' => 'ἰ', 
            'ἳ' => 'ἱ', 
            'ἴ' => 'ἰ', 
            'ἵ' => 'ἱ', 
            'ἶ' => 'ἰ', 
            'ἷ' => 'ἱ', 
            'Ἰ' => 'Ἰ', 
            'Ἱ' => 'Ἱ', 
            'Ἲ' => 'Ἰ', 
            'Ἳ' => 'Ἱ', 
            'Ἴ' => 'Ἰ', 
            'Ἵ' => 'Ἱ', 
            'Ἶ' => 'Ἰ', 
            'Ἷ' => 'Ἱ', 
            'ὀ' => 'ὀ', 
            'ὁ' => 'ὁ', 
            'ὂ' => 'ὀ', 
            'ὃ' => 'ὁ', 
            'ὄ' => 'ὀ', 
            'ὅ' => 'ὁ', 
            'Ὀ' => 'Ὀ', 
            'Ὁ' => 'Ὁ', 
            '὏' => 'Ὁ',  // <-- non-combining character in Perseus data 
            'Ὂ' => 'Ὀ', 
            'Ὃ' => 'Ὁ', 
            'Ὄ' => 'Ὀ', 
            'Ὅ' => 'Ὁ', 
            '὏' => 'Ὁ', 
            'ὐ' => 'ὐ', 
            'ὑ' => 'ὑ', 
            'ὒ' => 'ὐ', 
            'ὓ' => 'ὑ', 
            'ὔ' => 'ὐ', 
            'ὕ' => 'ὑ', 
            'ὖ' => 'ὐ', 
            'ὗ' => 'ὑ', 
            'Ὑ' => 'Ὑ', 
            'Ὕ' => 'Ὑ', 
            'Ὗ' => 'Ὑ', 
            'ὠ' => 'ὠ', 
            'ὡ' => 'ὡ', 
            'ὢ' => 'ὠ', 
            'ὣ' => 'ὡ', 
            'ὤ' => 'ὠ', 
            'ὥ' => 'ὡ', 
            'ὦ' => 'ὠ', 
            'ὧ' => 'ὡ', 
            'Ὠ' => 'Ὠ', 
            'Ὡ' => 'Ὡ', 
            'Ὢ' => 'Ὠ', 
            'Ὣ' => 'Ὡ', 
            'Ὤ' => 'Ὠ', 
            'Ὥ' => 'Ὡ', 
            'Ὦ' => 'Ὠ', 
            'Ὧ' => 'Ὡ', 
            'ὰ' => 'α', 
            'ά' => 'α', 
            'ὲ' => 'ε', 
            'έ' => 'ε', 
            'ὴ' => 'η', 
            'ή' => 'η', 
            'ὶ' => 'ι', 
            'ί' => 'ι', 
            'ὸ' => 'ο', 
            'ό' => 'ο', 
            'ὺ' => 'υ', 
            'ύ' => 'υ', 
            'ὼ' => 'ω', 
            'ώ' => 'ω', 
            'ᾀ' => 'ἀ', 
            'ᾁ' => 'ἁ', 
            'ᾂ' => 'ἀ', 
            'ᾃ' => 'ἁ', 
            'ᾄ' => 'ἀ', 
            'ᾅ' => 'ἁ', 
            'ᾆ' => 'ἀ', 
            'ᾇ' => 'ἁ', 
            'ᾈ' => 'Ἀ', 
            'ᾉ' => 'Ἁ', 
            'ᾊ' => 'Ἀ', 
            'ᾋ' => 'Ἁ', 
            'ᾌ' => 'Ἀ', 
            'ᾍ' => 'Ἁ', 
            'ᾎ' => 'Ἀ', 
            'ᾏ' => 'Ἁ', 
            'ᾐ' => 'ἠ', 
            'ᾑ' => 'ἡ', 
            'ᾒ' => 'ἠ', 
            'ᾓ' => 'ἡ', 
            'ᾔ' => 'ἠ', 
            'ᾕ' => 'ἡ', 
            'ᾖ' => 'ἠ', 
            'ᾗ' => 'ἡ', 
            'ᾘ' => 'Ἠ', 
            'ᾙ' => 'Ἡ', 
            'ᾚ' => 'Ἠ', 
            'ᾛ' => 'Ἡ', 
            'ᾜ' => 'Ἠ', 
            'ᾝ' => 'Ἡ', 
            'ᾞ' => 'Ἠ', 
            'ᾟ' => 'Ἡ', 
            'ᾠ' => 'ὠ', 
            'ᾡ' => 'ὡ', 
            'ᾢ' => 'ὠ', 
            'ᾣ' => 'ὡ', 
            'ᾤ' => 'ὠ', 
            'ᾥ' => 'ὡ', 
            'ᾦ' => 'ὠ', 
            'ᾧ' => 'ὡ', 
            'ᾨ' => 'Ὠ', 
            'ᾩ' => 'Ὡ', 
            'ᾪ' => 'Ὠ', 
            'ᾫ' => 'Ὡ', 
            'ᾬ' => 'Ὠ', 
            'ᾭ' => 'Ὡ', 
            'ᾮ' => 'Ὠ', 
            'ᾯ' => 'Ὡ', 
            'ᾰ' => 'α', 
            'ᾱ' => 'α', 
            'ᾲ' => 'α', 
            'ᾳ' => 'α', 
            'ᾴ' => 'α', 
            'ᾶ' => 'α', 
            'ᾷ' => 'α', 
            'Ᾰ' => 'α', 
            'Ᾱ' => 'Α', 
            'Ὰ' => 'Α', 
            'Ά' => 'Α', 
            'ᾼ' => 'Α', 
            'ῂ' => 'η', 
            'ῃ' => 'η', 
            'ῄ' => 'η', 
            'ῆ' => 'η', 
            'ῇ' => 'η', 
            'Ὲ' => 'Ε', 
            'Έ' => 'Ε', 
            'Ὴ' => 'Η', 
            'Ή' => 'Η', 
            'ῌ' => 'Η', 
            'ῐ' => 'ι', 
            'ῑ' => 'ι', 
            'ῒ' => 'ι', 
            'ΐ' => 'ι', 
            'ῖ' => 'ι', 
            'ῗ' => 'ι', 
            'Ῐ' => 'Ι', 
            'Ῑ' => 'Ι', 
            'Ὶ' => 'Ι', 
            'Ί' => 'Ι', 
            'ῠ' => 'υ', 
            'ῡ' => 'υ', 
            'ῢ' => 'υ', 
            'ΰ' => 'υ', 
            'ῤ' => 'ῤ', 
            'ῥ' => 'ῥ', 
            'ῦ' => 'υ', 
            'ῧ' => 'υ', 
            'Ῠ' => 'Υ', 
            'Ῡ' => 'Υ', 
            'Ὺ' => 'Υ', 
            'Ύ' => 'Υ', 
            'Ῥ' => 'Ῥ', 
            'ῲ' => 'ω', 
            'ῳ' => 'ω', 
            'ῴ' => 'ω', 
            'ῶ' => 'ω', 
            'ῷ' => 'ω', 
            'Ὸ' => 'Ο', 
            'Ό' => 'Ο', 
            'Ὼ' => 'Ω', 
            'Ώ' => 'Ω', 
            'ῼ' => 'Ω', 
            'ϊ' => 'ι', 
            'ϋ' => 'υ', 
            'ό' => 'ο', 
            'ύ' => 'υ', 
            'ώ' => 'ω', 
            'Ϊ' => 'Ι', 
            'Ϋ' => 'Υ', 
            'ά' => 'α', 
            'έ' => 'ε', 
            'ή' => 'η', 
            'ί' => 'ι', 
            'ΰ' => 'υ', 
        ); 
        return str_replace(
            array_keys($tr), 
            array_values($tr), 
            $str
        );
    }

    /*
     * Simple way to check if there are any accents. 
     *
     * @param string $str 
     * @access public
     * @return boolean
     */
    public function containsGreekAccents($str)
    {
        return $this->stripAccents($str) != $str;
    }

    /**
     * Switch graves to acutes, usually for easier matching. 
     * 
     * @param string $str 
     * @access public
     * @return string
     */
    public function gravesToAcutes($str) 
    {
        $tr = array(
            'ὰ' => 'ά', 
            'ἂ' => 'ἄ', 
            'ἃ' => 'ἅ', 
            'Ὰ' => 'Ά', 
            'Ἂ' => 'Ἄ', 
            'Ἃ' => 'Ἅ', 
            'ὲ' => 'έ', 
            'ἒ' => 'ἔ', 
            'ἓ' => 'ἕ', 
            'Ὲ' => 'Έ', 
            'Ἒ' => 'Ἔ', 
            'Ἓ' => 'Ἕ', 
            'ὴ' => 'ή',
            'ἢ' => 'ἤ', 
            'ἣ' => 'ἥ', 
            'Ὴ' => 'Ή',
            'Ἢ' => 'Ἤ', 
            'Ἣ' => 'Ἥ', 
            'ὶ' => 'ί', 
            'ἲ' => 'ἴ', 
            'ἳ' => 'ἵ', 
            'Ὶ' => 'Ί', 
            'Ἲ' => 'Ἴ', 
            'Ἳ' => 'Ἵ', 
            'ὸ' => 'ό', 
            'ὂ' => 'ὄ', 
            'ὃ' => 'ὅ', 
            'Ὸ' => 'Ό', 
            'Ὂ' => 'Ὄ', 
            'Ὃ' => 'Ὅ', 
            'ὺ' => 'ύ', 
            'ὒ' => 'ὔ', 
            'ὓ' => 'ὕ', 
            'Ὺ' => 'Ύ', 
            // 'ὒ' => 'ὔ',  //  Only appears as a capital 
            'Ὓ' => 'Ὕ', 
            'ὼ' => 'ώ',
            'ὢ' => 'ὤ', 
            'ὣ' => 'ὥ', 
            'Ὼ' => 'Ώ',
            'Ὢ' => 'Ὤ', 
            'Ὣ' => 'Ὥ', 
        ); 

        return str_replace(
            array_keys($tr), 
            array_values($tr), 
            $str
        );
    }

    /**
     * Remove breathings from a given character. 
     * 
     * @param string $str 
     * @access public
     * @return void
     */
    public function stripBreathings($str)
    {
        $map = array(
            //  Extended 
            'ἀ' => 'α', 
            'ἁ' => 'α', 
            'Ἀ' => 'Α', 
            'Ἁ' => 'Α', 
            'ἐ' => 'ε', 
            'ἑ' => 'ε', 
            'Ἐ' => 'Ε', 
            'Ἑ' => 'Ε', 
            'ἠ' => 'η', 
            'ἡ' => 'η', 
            'Ἠ' => 'Η', 
            'Ἡ' => 'Η', 
            'ἰ' => 'ι', 
            'ἱ' => 'ι', 
            'Ἰ' => 'Ι', 
            'Ἱ' => 'Ι', 
            'ὀ' => 'ο', 
            'ὁ' => 'ο', 
            'Ὀ' => 'Ο', 
            'Ὁ' => 'Ο', 
            'ὐ' => 'υ', 
            'ὑ' => 'υ', 
            'Ὑ' => 'Υ', 
            'Ὗ' => 'Υ', 
            'ὠ' => 'ω', 
            'ὡ' => 'ω', 
            'Ὠ' => 'Ω', 
            'Ὡ' => 'Ω', 
            'ῤ' => 'ρ', 
            'ῥ' => 'ρ', 
            'Ῥ' => 'Ρ', 
         );
         return str_replace(
            array_keys($map), 
            array_values($map),
            $str
         ); 
    }

    /**
     * Normal romanization respects capitals and uses macrons for long vowels.  
     * 
     * @param mixed $str 
     * @access public
     * @return void
     */
    public function romanize($str)
    {
        return $this->romanizeUnaccented($this->stripAccents($str)); 
    }

    /**
     * Romanise into ascii characters: Eta and Omega are capitalised E and O,
     * while all other letters are lowercase.  
     * 
     * @param mixed $str 
     * @access public
     * @return void
     */
    public function romanizeForAscii($str)
    {
        return str_replace(
            array('Ē', 'ē', 'Ō', 'ō'), 
            array('E', 'E', 'O', 'O'), 
            strtolower(
                $this->romanize($str) 
                ) 
        ); 
    }

    /**
     * Turn unaccented Unicode Greek into roman characters. 
     *
     * (Breathings are OK.)
     *
     * @param mixed $str Greek string, normalised characters, unaccented
     * @access public
     * @return void
     */
    public function romanizeUnaccented($str)
    { 
        //  Special case of rough breathings on the second letter. 
        $mapPrefix = array(
            'αἱ' => 'hai', 
            'αὑ' => 'hau', 
            'εὑ' => 'heu', 
            'οὑ' => 'hou', 
            'εἱ' => 'hei', 
            'οἱ' => 'hoi', 
            'εὑ' => 'heu', 
            'ηὑ' => 'hēu', 
            'υἱ' => 'hui', 
            'χὡ' => 'chō', 
            'χὡ' => 'chō', 
            'Αἱ' => 'Hai', 
            'Αὑ' => 'Hau', 
            'Εὑ' => 'Heu', 
            'Οὑ' => 'Hou', 
            'Εἱ' => 'Hei', 
            'Οἱ' => 'Hoi', 
            'Εὑ' => 'Heu', 
            'Ηὑ' => 'Hēu', 
            'Υἱ' => 'Hui', 
            'Χὡ' => 'Chō', 
            ); 
        $str2 = $str; 
        foreach ($mapPrefix as $greek => $english) { 
            //  echo mb_substr($str, 0, mb_strlen($greek)) . " == $greek\n"; 
            if (mb_substr($str, 0, mb_strlen($greek)) == $greek) { 
                $str2 = $english . mb_substr($str, mb_strlen($greek)); 
                break; 
            }
        }

        //  Regular replace. 
        $map = array( 
            //  Combinations must be caught first...  
            'γκ' => 'nk', 
            'γχ' => 'nch', 
            'γγ' => 'ng', 
            //  Breathings 
            'ἀ' => 'a', 
            'ἁ' => 'ha', 
            'Ἀ' => 'A', 
            'Ἁ' => 'Ha', 
            'ἐ' => 'e', 
            'ἑ' => 'he', 
            'Ἐ' => 'E', 
            'Ἑ' => 'He', 
            'ἠ' => 'ē', 
            'ἡ' => 'hē', 
            'Ἠ' => 'Ē', 
            'Ἡ' => 'Hē', 
            'ἰ' => 'i', 
            'ἱ' => 'hi', 
            'Ἰ' => 'I', 
            'Ἱ' => 'Hi', 
            'ὀ' => 'o', 
            'ὁ' => 'ho', 
            'Ὀ' => 'O', 
            'Ὁ' => 'Ho', 
            'ὐ' => 'u', 
            'ὑ' => 'hu', 
            'Ὑ' => 'Hu', 
            'Ὗ' => 'Hu', 
            'Ὕ' => 'Hu', 
            'ὠ' => 'ō', 
            'ὡ' => 'hō', 
            'Ὠ' => 'Ō', 
            'Ὡ' => 'Hō', 
            'ῤ' => 'r', 
            'ῥ' => 'rh', 
            'Ῥ' => 'Rh', 
            //  Normal and obsolete letters... 
            'Α' => 'A', 
            'Β' => 'B', 
            'Γ' => 'G', 
            'Δ' => 'D', 
            'Ε' => 'E', 
            'Ζ' => 'Z', 
            'Η' => 'Ē', 
            'Θ' => 'Th', 
            'Ι' => 'I', 
            'Κ' => 'K', 
            'Λ' => 'L', 
            'Μ' => 'M', 
            'Ν' => 'N', 
            'Ξ' => 'X', 
            'Ο' => 'O', 
            'Π' => 'P', 
            'Ρ' => 'R', 
            'Σ' => 'S', 
            'Τ' => 'T', 
            'Υ' => 'U', 
            'Φ' => 'Ph', 
            'Χ' => 'Ch', 
            'Ψ' => 'Ps', 
            'Ω' => 'Ō', 
            'α' => 'a', 
            'β' => 'b', 
            'γ' => 'g', 
            'δ' => 'd', 
            'ε' => 'e', 
            'ζ' => 'z', 
            'η' => 'ē', 
            'θ' => 'th', 
            'ι' => 'i', 
            'κ' => 'k', 
            'λ' => 'l', 
            'μ' => 'm', 
            'ν' => 'n', 
            'ξ' => 'x', 
            'ο' => 'o', 
            'π' => 'p', 
            'ρ' => 'r', 
            'ς' => 's', 
            'σ' => 's', 
            'τ' => 't', 
            'υ' => 'u', 
            'φ' => 'ph', 
            'χ' => 'ch', 
            'ψ' => 'ps', 
            'ω' => 'ō', 
            'ϐ' => 'b', 
            'ϑ' => 'th', 
            'ϒ' => 'g', 
            'ϓ' => 'g', 
            'ϔ' => 'g', 
            'ϕ' => 'ph', 
            'ϖ' => 'ō', 
            'ϗ' => '&', 
            'Ϙ' => 'Q',  // koppa
            'ϙ' => 'q',  // koppa
            'Ϛ' => 'S',  // stigma 
            'ϛ' => 's',  // stigma 
            'Ϝ' => 'W',  // digamma 
            'ϝ' => 'w',  // digamma 
            'Ϟ' => 'Q',  // koppa (alternate)
            'ϟ' => 'q',  // koppa (alternate)
            'Ϡ' => 'Ts',  // sampi
            'ϡ' => 'ts',  // sampi
        ); 
        $str3 = str_replace(
            array_keys($map), 
            array_values($map),
            $str2 
        ); 
        //  Remove any remaining characters? 
        //  Should not be needed. 
        return $str3; 
    } 

    public function romanizePunctuation($str)
    { 
        $punctuation = array( 
            'ʹ' => '\'', 
            '͵' => ',', 
            'ͺ' => ' ', 
            ';' => '?', 
            '·' => ';', 
        ); 
    }

    /*
     * Splitters 
     * ------------------------------------------------------------
     *
     */

    /**
     * Extract every word in a chapter, returning its prefix and suffix
     * punctuation, plus its book:chapter:verse and paragraph:sentence:word
     * numbers (Use default $b and $c for book and chapter).
     *
     * [ i => [b, c, v, p, s, w, prefix, word, suffix], ... ]
     *
     * The immediate application of this function is ripping chapters of text
     * files into the database. It may require further generalisation. 
     *
     * @todo Expand for book/section/chapter/verse if/when Reference is
     * upgraded to 3-level depth. 
     * 
     * @param string $text 
     * @param int $b 
     * @param int $c 
     * @return array
     */
    public function getStructure($str, $b, $c)
    {
        $structure = []; 
        $v = 1; // default
        $ps = $this->getParagraphsAndSentences($str); 
        foreach ($ps as $p => $paragraph) { 
            foreach ($paragraph as $s => $sentence) { 
                $triples = $this->matchWordsAndReferenceNumbers($sentence); 
                $w = 1; 
                foreach ($triples as $triple) { 
                    list($prefix, $word, $suffix) = $triple; 
                    if ((int)$word != 0) { 
                        $v = (int)$word; 
                    } else { 
                        //  echo "[$b, $c, $v, $p, $s, $w, $prefix, $word, $suffix]\n"; 
                        $structure[] = [
                            $b, $c, $v,
                            $p, $s, $w, 
                            $prefix, $word, $suffix, 
                        ]; 

                        $w++; 
                    }
                }
            }
        }
        return $structure; 
    }

    /**
     * Return a 2D array of paragraphs and sentences from a section of Greek
     * text. 
     * 
     * @param string $text 
     * @access public
     * @return void
     */
    public function getParagraphsAndSentences($str)
    {
        return $this->splitGreekSentences($this->splitParagraphs($str)); 
    }

    /**
     * splitParagraphs; recurse into arrays. 
     *
     * @todo Add option to treat lines containing only spaces as empty lines.
     *
     * @see $this->getParagraphsAndSentences(); 
     * 
     * @param string|array $str 
     * @access public
     * @return array
     */
    public function splitParagraphs($str, $trimSpacesFromTheEndOfLines=true) 
    {
        if (is_array($str)) { 
            $result = [];
            foreach ($str as $element) { 
                $result[] = $this->splitParagraphs($element); 
            }
            return $result; 
        } elseif (is_string($str)) { 
            if ($trimSpacesFromTheEndOfLines) { 
                $str = preg_replace('/ +$/m', '', $str); 
            }
            $str = str_replace("\r\n", "\n", $str);
            $str = str_replace("\r", "\n", $str);
            $result = preg_split("/\n{2,}/", $str); 
            return $this->renumberArray($result, $from=1); 
        } else { 
            throw new \Exception("Argument 1 must be string or array."); 
        }
    }

    /**
     * splitGreekSentences; recurse into arrays.
     *
     * @see getParagraphsAndSentences(); 
     * 
     * @param string $str 
     * @access public
     * @return void
     */
    public function splitGreekSentences($str) 
    {
        if (is_array($str)) { 

            $result = [];
            foreach ($str as $element) { 
                $result[] = $this->splitGreekSentences($element); 
            }

        } else { 

            //  Two kinds of ';'
            $firstPass =  preg_split("/([;;.]+)\s*/u", $str, -1, PREG_SPLIT_DELIM_CAPTURE); 

            //  Now reattach the delimiters to their sentences; we'll do this
            //  rather than use preg_match as there may not be a final
            //  delimiter in any given block (e.g. sentences may span chapter
            //  divisions.)

            $result = [];
            $i = 0; 
            while ($sentence = $this->ifx($firstPass, $i++, false)) { 
                if ($delimiter = trim($this->ifx($firstPass, $i++, false))) { 
                    if (strlen(trim($sentence)) > 0) { 
                        $result[] = trim($sentence) . trim($delimiter); 
                    }
                }
            }

        }

        return $this->renumberArray($result, $from=1); 
    }

    /*
     * Convenience functions 
     * ------------------------------------------------------------
     *
     */

    /**
     * Convenience function; get array value if key exists. 
     * 
     * @param array $arr 
     * @param string|int $key 
     * @param mixed $else 
     * @return mixed
     */
    function ifx($arr, $key, $else=null)
    {
        if (isset($arr[$key])) {
            return $arr[$key];
        } else {
            return $else;
        }
    }

    /**
     * Convenience function: Renumber an array's keys: 1..n. 
     *
     * @see splitGreekSentences, splitParagraphs
     * 
     * @param mixed $array 
     * @param int $from 
     * @param mixed $recurse 
     * @return array
     */
    public function renumberArray($array, $from=1, $recurse=false)
    {
        $newArray = [];
        $i = (int)$from; 
        foreach ($array as $element) {
            if ($recurse and is_array($element)) { 
                $newArray[$i++] = $this->renumberArray($element); 
            } else { 
                $newArray[$i++] = $element; 
            }
        }
        return $newArray; 
    }

    /*
     * Random fun stuff... 
     * ------------------------------------------------------------
     *
     */

    /**
     * Using enumeration of Greek numbers, as in gematria.  
     *
     * @param mixed $values 
     * @access public
     * @return void
     */
    public function numericValue($str)
    {
        $map = array(
            'α' => 1, 
            'Α' => 1, 
            'β' => 2, 
            'Β' => 2, 
            'γ' => 3, 
            'Γ' => 3, 
            'δ' => 4, 
            'Δ' => 4, 
            'ε' => 5, 
            'Ε' => 5, 
            'ζ' => 7, 
            'Ζ' => 7, 
            'η' => 8, 
            'Η' => 8, 
            'θ' => 9, 
            'Θ' => 9, 
            'ι' => 10, 
            'Ι' => 10, 
            'κ' => 20, 
            'Κ' => 20, 
            'λ' => 30, 
            'Λ' => 30, 
            'μ' => 40, 
            'Μ' => 40, 
            'ν' => 50, 
            'Ν' => 50, 
            'ξ' => 60, 
            'Ξ' => 60, 
            'ο' => 70, 
            'Ο' => 70, 
            'π' => 80, 
            'Π' => 80, 
            'ϟ' => 90,  // koppa
            'Ϟ' => 90,  // koppa
            'ρ' => 100, 
            'Ρ' => 100, 
            'σ' => 200, 
            'Σ' => 200, 
            'ς' => 200, 
            'Σ' => 200, 
            'τ' => 300, 
            'Τ' => 300, 
            'υ' => 400, 
            'Υ' => 400, 
            'φ' => 500, 
            'Φ' => 500, 
            'χ' => 600, 
            'Χ' => 600, 
            'ψ' => 700, 
            'Ψ' => 700, 
            'ω' => 800, 
            'Ω' => 800, 
            'ϡ' => 900,  // sampi 
            'Ϡ' => 900,  // sampi 
        ); 
        $standardForm = $this->stripBreathings($this->stripAccents($str)); 
        $letters = $this->getCharacters($standardForm); 
        $total = 0; 
        foreach ($letters as $letter) { 
            if ($x = $this->ifx($map, $letter, false)) { 
                //  echo "$letter: $x\n";
                $total += (int)$x;
            } else { 
                //  echo "$letter: --\n";
            }
        }
        return $total; 
    }
}

