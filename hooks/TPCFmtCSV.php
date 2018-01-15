<?php

/**
  * Parser for CSV strings.
  * Registers the following template hooks:
  *
  * {{csv}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtCSV {

	public static function onCSV( &$tpcState, &$title, &$temp ) {
		$id = TPCUtil::dictGet( $temp->params['id'] );
		$tl = TPCUtil::dictGet( $temp->params['tl'] );
		if ( empty( $id ) or empty( $tl ) ) {
			return true;
		}
		$tl = TPCUtil::sanitize( $tl, false );

		$subkey = &$tpcState->jsonContents;
		$ids = explode( ".", $id );
		$lastSubkeyIndex = count( $ids ) - 1;
		for( $i = 0; $i < $lastSubkeyIndex; $i++ ) {
			$subkey = &$subkey[ $ids[ $i ] ];
		}
		$subkey[ $ids[ $lastSubkeyIndex ] = &$tl;
		return true;
	}
}
$wgTPCHooks['csv'][] = 'TPCFmtCSV::onCSV';
