<?php

/**
  * Parser for binary hacks.
  * Registers the following template hooks:
  *
  * {{thcrap_binhack}}
  *
  * @file
  * @author Nmlgc
  */

class TPCBinhack {

	public static function onBinhack( &$tpcState, $title, $temp ) {
		$id = ( $temp->params['id'] ?? null );
		if ( !$id ) {
			// Nope, we're not even trying to create GUIDs
			return true;
		}
		// Switch back to the top file of the current game
		$tpcState->switchGameFile( null );
		$baseFile = &$tpcState->getBuild( null );

		$title = ( $temp->params['title'] ?? null );
		$addr = ( $temp->params['addr'] ?? null );
		$code = ( $temp->params['code'] ?? null );
		// Only makes sense once we have a GUI
		// $desc = ( $temp->params['desc'] ?? null );
		// $dasm = ( $temp->params['dasm'] ?? null );

		// TODO: Refactor into something callback-based?
		$addr = TPCParse::parseVer( $addr );
		foreach ( $addr as $build => $val ) {
			$buildFile = &$tpcState->getBuild( $build );
			$valArray = TPCParse::parseCSV( $val );
			if ( !empty( $valArray ) ) {
				$buildFile['binhacks'][$id]['addr'] = $valArray;
			}
		}

		$code = TPCParse::parseVer( $code );
		foreach ( $code as $build => $val ) {
			$buildFile = &$tpcState->getBuild( $build );
			$val = preg_replace( '/\s+/', '', $val );
			if ( $val !== "" ) {
				$buildFile['binhacks'][$id]['code'] = $val;
			}
		}

		$cont = &$baseFile['binhacks'][$id];
		if ( !empty( $title ) ) {
			$cont['title'] = $title;
		}
		return true;
	}
}

TouhouPatchCenter::registerRestrictedHook( 'thcrap_binhack', 'TPCBinhack::onBinhack' );
