<?php

/**
  * Parser for hardcoded strings.
  * Registers the following template hooks:
  *
  * {{thcrap_string_def}}
  * {{thcrap_string_loc}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtStrings {

	public static function onDef( &$tpcState, &$title, &$temp ) {
		$id = ( $temp->params['id'] ?? null );
		$tl = ( $temp->params['tl'] ?? null );
		if ( empty( $id ) or empty( $tl ) ) {
			return true;
		}
		$tl = TPCUtil::sanitize( $tl, false );
		if ( isset( $temp->params['ascii'] ) ) {
			// Try to transliterate the string, then limit it to the ASCII range
			// The exact transliteration results are up to the version of libiconv that PHP is
			// linked to, so let's at least define some clear rules for whitespace.
			$tl = str_replace( [ "\u{00a0}", "\u{3000}" ], ' ', $tl );

			$tl = iconv( "UTF-8", "ASCII//TRANSLIT//IGNORE", $tl );
			$tl = preg_replace( '/[^(\x20-\x7F)]/i', '', $tl );
		}
		$stringdefs = &$tpcState->switchFile( "stringdefs.js" );
		$stringdefs[$id] = $tl;
		return true;
	}

	public static function onLoc( &$tpcState, &$title, &$temp ) {
		$addr = ( $temp->params['addr'] ?? null );
		$id = ( $temp->params['id'] ?? null );
		if ( empty( $addr ) or empty( $id ) ) {
			return true;
		}
		$stringdefs = &$tpcState->switchGameFile( "stringlocs.js" );

		$builds = TPCParse::parseVer( $addr );
		foreach ( $builds as $build => $val ) {
			if ( $val !== "" ) {
				$buildFile = &$tpcState->getBuild( $build );
				$buildFile[$val] = $id;
			}
		}
		return true;
	}
}
TouhouPatchCenter::registerHook( 'thcrap_string_def', 'TPCFmtStrings::onDef' );
TouhouPatchCenter::registerHook( 'thcrap_string_loc', 'TPCFmtStrings::onLoc' );
// Short versions
TouhouPatchCenter::registerHook( 'stringdef', 'TPCFmtStrings::onDef' );
TouhouPatchCenter::registerHook( 'stringloc', 'TPCFmtStrings::onLoc' );
