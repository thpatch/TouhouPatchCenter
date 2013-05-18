<?php

/**
  * Scrapes templates on a MediaWiki page into an array.
  *
  * @file
  * @author Nmlgc
  */

/**
  * This was my first attempt at writing own PHP code. Profiling and optimizing
  * this was quite a nice way to find myself around PHP and see what kind of
  * concepts work best in this language (not to mention fun).
  *
  * Compared to the prototype version in Python, this has become a vastly
  * different beast. Basically, it works like this:
  *
  * - Function calls are *expensive*. Avoid if possible.
  * - As a result, "classic" string parsing (especially when substr() is 
  *   involved) is *SLOW*.
  *   Thus, defer the actual string handling as long as possible.
  * - Instead, create an array containing the offsets of all interesting 
      MediaWiki tokens.
  * - All the nesting functions work exclusively on this array. Subfunctions 
  *   only get references into this array.
  * - Only the last function, Template->add, actually splits the string for
  *   the array assignment.
  * - Pass constant parameters as &$reference for an extra free speed boost!
  *
  * This way, we can achieve a ~32x speed increase compared to the initial
  * dumb Python→PHP conversion. :-)
  */

class Template
{
	// Template name
	public $name = '';

	// Parsed template parameters
	public $params = array();	

	// Number of next unnamed parameter. 0 = template name
	public $unnamedId = 0;

	// Start and end offsets of this template in the source string
	public $srcStart, $srcEnd;

	/** 
	  * Adds an element (name or (unnamed) parameter) to this template.
	  *
	  * @param string $str 
	  * @param int $start Offset of parameter's first character in $str
	  * @param int $end Offset of parameter's last character in $str
	  * @param int $assign Offset of assignment character. Can be null for unnamed parameters.
	  *  
	  */
	public function add( &$str, &$start, &$assign, &$end )	{
		// "|param = value"
		if ( $assign )	{
			$key = trim( substr( $str, $start, $assign - $start ) );
			$assign++;	// Jump over assign character
			$value = trim( substr( $str, $assign, $end - $assign ) );
			$this->params[$key] = $value;
		// "value"
		} else {
			$value = trim( substr( $str, $start, $end - $start ) );
			if ( $this->unnamedId === 0 ) {
				// First one, i.e. the template name
				$this->name = $value;
			} else {
				// Any other unnamed template parameter
				$this->params[$this->unnamedId] = $value;
			}
			$this->unnamedId++;
		}
	}
}

class MWScrape {
	// MediaWiki Syntax
	const MW_TL = '{{';
	const MW_TR = '}}';
	const MW_LL = '[[';
	const MW_LR = ']]';
	const MW_PIPE = '|';
	const MW_ASSIGN = '=';
	const MW_TOKEN_REGEX = '/=|\||{{|}}|\[\[|\]\]/';
	// Group 2: Custom page title (if given)
	// Group 3: Display title
	const MW_PAGE_LINK_REGEX = '/\[\[((.*?)\|)?(.*?)\]\]/';

	const MW_TEMPLATE_TOKEN_LEN = 2;

	/**
	  * Parses the template given by $str[$start:$end] into a Template object.
	  *
	  * @param string $str Wikitext string
	  * @param array $tokens Token array (generated by getMWTokenArray)
	  * @param int $start Offset of the token opening the template in $str
	  * @param int $end Offset of the token closing the template in $str
	  * @return Template Template object
	  */
	protected static function parseTemplate( &$str, &$tokens, &$start, &$end ) {
		$ret = new Template;
		$ret->srcStart = $tokens[$start][1];
		$ret->srcEnd = $tokens[$end][1];

		$nest = 0;
		// $start will point to the first character of the opening token.
		// We jump over it for the first parameter *and* skip its evaluation
		// in the loop - for the correct nesting level.
		$paramOff = $ret->srcStart + self::MW_TEMPLATE_TOKEN_LEN;
		$assignOff = null;

		// We need to iterate through the whole token array again
		// to catch nested templates here as well
		for ( $i = $start + 1; $i < $end; $i++ ) {
			$curOff = $tokens[$i][1];
			$curToken = $tokens[$i][0];
			if ( $nest === 0 and $curToken === self::MW_PIPE )	 {
				$ret->add( $str, $paramOff, $assignOff, $curOff );
				$paramOff = $curOff + 1;
				$assignOff = null;
			} elseif ( $curToken === self::MW_ASSIGN and $assignOff === null ) {
				$assignOff = $curOff;
			} elseif ( $curToken === self::MW_TL || $curToken === self::MW_LL ) {
				$nest++;
			} elseif ( $curToken === self::MW_TR || $curToken === self::MW_LR ) {
				$nest--;
			}
		}
		// Last element
		$ret->add( $str, $paramOff, $assignOff, $ret->srcEnd );
		return $ret;
	}

	/** 
	 * Create an array with offsets of all interesting tokens in a wikitext page.
	 *
	 * @param string $str Wikitext string
	 * @return array Array of the form
	 * Array (
	 * 		[index] => Array (
	 *			[0] = <token>
	 *			[1] = <offset>
	 *		)
	 * )
	 */
	protected static function getMWTokenArray( &$str ) {
		// The regex way of doing this becomes faster the more matches there are.
		// For shorter pages, this may actually be a bit slower than calling strpos()
		// repeatedly across the whole string for every token, but not by much.
		preg_match_all( self::MW_TOKEN_REGEX, $str, $tokens, PREG_OFFSET_CAPTURE );
		return $tokens[0];
	}

	/** 
	  * Parses templates and their parameters of a MediaWiki page into an array.
	  *
	  * @param string $page Wikitext string containing the full page code
	  * @return array Array of Template objects
	  */
	public static function toArray( &$page ) {
		$temps = array();

		// Apply basic regex
		$page = preg_replace( self::MW_PAGE_LINK_REGEX, "$3", $page );

		$tokens = self::getMWTokenArray( $page );
		$tokenCount = count( $tokens );

		$nest = 0;
		$tempOff = 0;
		for ( $i = 0; $i < $tokenCount; $i++ ) {
			$curOff = $tokens[$i][1];
			$curToken = $tokens[$i][0];
			if ( $curToken === self::MW_TL ) {
				if ( $nest === 0 ) {
					$tempOff = $i;
				}
				$nest++;
			} elseif ( $curToken === self::MW_TR ) {
				$nest--;
				if ( $nest === 0 )	{
					$temps[] = self::parseTemplate( $page, $tokens, $tempOff, $i );
				}
			}
		}
		return $temps;
	}
}