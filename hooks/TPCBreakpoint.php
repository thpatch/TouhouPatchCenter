<?php

/**
  * Parser for breakpoints.
  * Registers the following template hooks:
  *
  * {{thcrap_breakpoint}}
  *
  * @file
  * @author Nmlgc
  */

class TPCBreakpoint {

	public static function onBreakpoint( $tpcState, $title, $temp ) {
		$type = ( $temp->params['type'] ?? null );
		if ( !$type ) {
			// no type, invalid breakpoint
			return true;
		}
		// Switch back to the top file of the current game
		$tpcState->switchGameFile( null );

		foreach ( $temp->params as $key => $val ) {
			if ( $key === "type" ) {
				// nope, have that one already
				continue;
			}
			$builds = TPCParse::parseVer( $val );
			foreach ( $builds as $build => $val ) {
				$buildFile = &$tpcState->getBuild( $build );
				$val = trim( $val );
				if ( $val == "false" ) {
					$val = false;
				} else if ( $val == "true" ) {
					$val = true;
				} else if ( $key === "addr" ) {
					$val = TPCParse::parseCSV( $val );
				}
				$buildFile['breakpoints'][$type][$key] = $val;
			}
		}
		return true;
	}
}

TouhouPatchCenter::registerRestrictedHook( 'thcrap_breakpoint', 'TPCBreakpoint::onBreakpoint' );
