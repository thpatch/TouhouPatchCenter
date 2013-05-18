<?php

/**
  * Prints public server information from $wgTPCServers into a wiki table
  * and does some GeoIP (if available) for good measure.
  *
  * @file
  * @author Nmlgc
  */

class thcrap_servers {

	const TEMPLATE_NAME = 'thcrap_servers';

	public static function run( &$parser, &$cache, &$magicWordId, &$ret ) {
		if ( $magicWordId != self::TEMPLATE_NAME ) {
			return true;
		}

		global $wgTPCServers;
		$doGeoIP = function_exists( 'geoip_record_by_name' );

		// Table header
		$ret = "{| class=\"wikitable\"\n|-\n! Server\n! URL";
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
			}
			$ret .= "\n";
		}
		$ret .= "\n|}";
		return true;
	}

	/**
	  * MagicWordwgVariableIDs hook.
	  */
	public static function setup( &$variableIDs ) {
		$variableIDs[] = self::TEMPLATE_NAME;
		return true;
	}
}

$wgHooks['MagicWordwgVariableIDs'][] = 'thcrap_servers::setup';
$wgHooks['ParserGetVariableValueSwitch'][] = 'thcrap_servers::run';
