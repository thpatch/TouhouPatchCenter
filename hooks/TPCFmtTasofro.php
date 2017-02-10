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
		if( !isset( $tpcState->tasofroCode )) {
			$tpcState->tasofroCode = 0;
		}
		$code = TPCUtil::dictGet( $temp->params['code'], ++$tpcState->tasofroCode );
		$tpcState->tasofroCode = $code;
		$lines = TPCParse::parseLines( $temp->params['tl'] );

		$tpcState->jsonContents[$code]['lines'] = &$lines;
		return true;
	}
}

// Short versions
$wgTPCHooks['tt'][] = 'TPCFmtTasofro::onTT';
