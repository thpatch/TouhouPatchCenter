<?php

/**
  * Prints public server information from $wgTPCServers into a wiki table
  * and does some GeoIP (if available) for good measure.
  *
  * @file
  * @author Nmlgc
  */

class thcrap_servers extends TPCTemplate {

	public static function run( &$parser, &$cache, &$magicWordId, &$ret, &$frame ) {
		global $wgTPCServers;
		$doGeoIP = function_exists( 'geoip_record_by_name' );

		// Table header
		$ret = "{| class=\"wikitable\"\n|-\n! Server\n! URL\n";
		if ( $doGeoIP ) {
			$ret .= "\n! Location\n";
		}
		foreach ( $wgTPCServers as $i ) {
			if ( !is_array( $i ) ) {
				continue;
			}
			$ret .= "|-\n";
			$ret .= "| {$i['title']}\n";
			$ret .= "| [{$i['url']} {$i['url']}]\n";
			$ret .= TPCUtil::getGeoIPCell( $i['url'] );
		}
		$ret .= "|}";
		return true;
	}
}
