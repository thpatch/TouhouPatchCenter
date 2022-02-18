<?php

/**
  * Parser for Tasogare Frontier dialogs.
  * Registers the following template hooks:
  *
  * {{tt}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtTasofro {

	public static function onTT( &$tpcState, &$title, &$temp ) {
		$code = $tpcState->autoCode( $temp );

		// Unsupported by the Tasofro format patchers
		$tl = $temp->params['tl'];
		$tl = preg_replace( "/'''''(.*?)'''''/", '*\1*', $tl );
		$tl = preg_replace( "/'''(.*?)'''/", '*\1*', $tl );
		$tl = preg_replace( "/''(.*?)''/", '*\1*', $tl );

		$lines = TPCParse::parseLines( $tl, false );
		// Don't write a JSON null for empty boxes to keep patch stacking functional.
		if( $lines ) {
			$tpcState->jsonContents[$code]['lines'] = &$lines;
		}
		return true;
	}

	public static function onTTStageName( &$tpcState, &$title, &$temp ) {
		$tl = $temp->params['tl'];

		$lines = TPCParse::parseLines( $tl, false );
		// Don't write a JSON null for empty boxes to keep patch stacking functional.
		if( $lines ) {
			$tpcState->jsonContents['stagename'] = &$lines;
		}
		return true;
	}
}

// Short versions
$wgTPCHooks['tt'][] = 'TPCFmtTasofro::onTT';
$wgTPCHooks['tt/stagename'][] = 'TPCFmtTasofro::onTTStageName';
