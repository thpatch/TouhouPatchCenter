<?php

/**
  * Parsers for certain special syntax.
  *
  * @file
  * @author Nmlgc
  */

class TPCParse {
	/**
	  * Parses thcrap_ver.
	  */
	public static function parseVer( $str ) {
		$scrape = MWScrape::toArray( $str );
		$ret = array();
		$versions = array();
		// Splice points in the original string
		$splice = array();
		$cut = 0;
		// Find valid versions
		foreach ( $scrape as $i ) {
			if (
				( $i->name === "thcrap_ver" ) and	// TODO: Don't hardcode...
				( isset( $i->params['ver'] ) ) and
				( isset( $i->params[1] ) )
			) {
				$versions[] = $i;

				// Cut this template out of the original string
				$start = $i->srcStart - $cut;
				$end = $i->srcEnd - $cut + MWScrape::MW_TEMPLATE_TOKEN_LEN;
				$len = $end - $start;

				$str = substr( $str, 0, $start ) . substr( $str, $end );
				// Remember splice point
				$splice[] = $start;
				$cut += $len;
			}
		}
		// Splice each version
		$curSplice = 0;
		$spliceDrift = 0;
		$last = $str;
		foreach ( $versions as $i ) {
			$splicePoint = $splice[$curSplice] + $spliceDrift;
			// Splice
			$last =
				// before
				substr( $last, 0, $splicePoint ) .
				// this version
				$i->params[1] .
				// after
				substr( $last, $splicePoint )
			;
			$ret[ $i->params['ver'] ] = $last;
			$curSplice++;
			$spliceDrift += strlen( $i->params[1] );
		}
		$ret[null] = $str;
		return $ret;
	}

	/**
	  * Parses a CSV string into a flexible array.
	  *
	  * @param string &$param The string to split.
	  * @return array|string Flexible array.
	  */
	public static function parseCSV( &$param ) {
		$REGEX_CSV = '/\s*,\s*/';
		$ret = preg_split( $REGEX_CSV, $param, -1, PREG_SPLIT_NO_EMPTY );
		if ( count( $ret ) == 1 ) {
			return $ret[0];
		} else {
			return $ret;
		}
	}

	/**
	  * Parses a wikitext string into an array of lines.
	  *
	  * @param ?string &$param The string to split.
	  * @param bool $escape_percents Keep literal percent signs for a printf format string.
	  * @return array Array of lines.
	  */
	public static function parseLines( &$param, $escape_percents = true ) {
		$REGEX_LINE = '#<br\s*/?>|\n#';

		// Important! Breaks patch stacking otherwise!
		if ( ( $param === null ) || ( strlen( $param ) == 0 ) ) {
			return null;
		}
		$param = TPCUtil::sanitize( $param, $escape_percents );
		$tlnotePos = strpos( $param, json_decode( '"\u0014"' ) );
		if( $tlnotePos !== FALSE ) {
			$tlnote = substr( $param, $tlnotePos );
			$regular = substr( $param, 0, $tlnotePos );
			$ret = preg_split( $REGEX_LINE, $regular ) ;
			$ret[ count($ret) - 1] .= preg_replace("#<br\s*/?>#", "", $tlnote);
			return $ret;
		}
		return preg_split( $REGEX_LINE, $param );
	}

	/**
	  * Searches the given line for a ruby annotation.
	  *
	  * @param array &$matches Search results, in preg_match() format.
	  * @param string &$line The line of text to search.
	  * @return mixed The return value from preg_match().
	  */
	public static function parseRuby( &$matches, &$line ) {
		$REGEX_RUBY = '/{{\s*ruby(-ja)*\s*\|\s*(.*?)\s*\|\s*(.*?)\s*(\|.*)*}}/';
		return preg_match( $REGEX_RUBY, $line, $matches, PREG_OFFSET_CAPTURE );
	}

	/**
	  * Parses function calls. For example,
	  *
	  * 	parseFunc( "util_xor(0x77, 7, 16)" );
	  *
	  * would return:
	  *
	  * 	Array (
	  * 		[util_xor] => Array (
	  * 			[0] => 0x77, [1] => 7, [2] => 16
	  *			)
	  *		)
	  */
	public static function parseFunc( $str ) {
		if ( preg_match( '/(.+)\s*\((.+)\)/', $str, $func ) ) {
			$vars = self::parseCSV( $func[2] );
			return array( $func[1] => $vars );
		}
		return null;
	}
}
