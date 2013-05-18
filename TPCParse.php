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
		foreach ( $versions as $i ) {
			// Splice
			$ret[ $i->params['ver'] ] = 
				// before
				substr( $str, 0, $splice[$curSplice] ) .
				// this version
				$i->params[1] .
				// after
				substr( $str, $splice[$curSplice] )
			;
			$curSplice++;
		}
		$ret[null] = $str;
		return $ret;
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
			$vars = preg_split( '/\s*,\s*/', $func[2], null, PREG_SPLIT_NO_EMPTY );
			return array( $func[1] => $vars );
		}
		return null;
	}
}
