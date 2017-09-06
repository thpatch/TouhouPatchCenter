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

		$lines = TPCParse::parseLines( $temp->params['tl'], false );
		// Don't write a JSON null for empty boxes to keep patch stacking functional.
		if( $lines ) {
			$tpcState->jsonContents[$code]['lines'] = &$lines;
		}
		return true;
	}
}

// Short versions
$wgTPCHooks['tt'][] = 'TPCFmtTasofro::onTT';
