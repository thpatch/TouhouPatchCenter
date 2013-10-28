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
			if ( $doGeoIP ) {
				$url = parse_url( $i['url'], PHP_URL_HOST );
				$record = geoip_record_by_name( $url );

				$ret .= "| ";
				if ( $record['city'] ) {
					$ret .= "{$record['city']}, ";
				}
				if ( $record['region'] ) {
					$ret .= "{$record['region']}, ";
				}
				$ret .= "{$record['country_name']}, ";
				$ret .= "{$record['continent_code']}";
				$ret .= "\n";
			}
		}
		$ret .= "|}";
		return true;
	}
}

$wgHooks['MagicWordwgVariableIDs'][] = 'thcrap_servers::setup';
