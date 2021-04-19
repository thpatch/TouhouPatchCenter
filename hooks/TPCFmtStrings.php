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
		$id = TPCUtil::dictGet( $temp->params['id'] );
		$tl = TPCUtil::dictGet( $temp->params['tl'] );
		if ( empty( $id ) or empty( $tl ) ) {
			return true;
		}
		$tl = TPCUtil::sanitize( $tl, false );
		if ( isset( $temp->params['ascii'] ) ) {
			// Try to transliterate the string, then limit it to the ASCII range
			$tl = iconv( "UTF-8", "ASCII//TRANSLIT//IGNORE", $tl );
			$tl = preg_replace( '/[^(\x20-\x7F)]/i', '', $tl );
		}
		$stringdefs = &$tpcState->switchFile( "stringdefs.js" );
		$stringdefs[$id] = $tl;
		return true;
	}

	public static function onLoc( &$tpcState, &$title, &$temp ) {
		$addr = TPCUtil::dictGet( $temp->params['addr'] );
		$id = TPCUtil::dictGet( $temp->params['id'] );
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
