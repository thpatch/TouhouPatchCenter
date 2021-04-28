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
	public static function workAroundZero( &$arr, $id ) {
		if( $id == 0 ) {
			$arr[ '_' ] = '_';
		}
	}

	public static function onCSV( &$tpcState, &$title, &$temp ) {
		$id = ( $temp->params['id'] ?? null );
		$tl = ( $temp->params['tl'] ?? null );
		if ( $tl === "" ) {
			return true;
		}
		$tl = TPCUtil::sanitize( $tl, false );

		$subkey = &$tpcState->jsonContents;
		$ids = explode( ".", $id );
		$lastSubkeyIndex = count( $ids ) - 1;
		for( $i = 0; $i < $lastSubkeyIndex; $i++ ) {
			self::workAroundZero( $subkey, $ids[ $i ] );
			$subkey = &$subkey[ $ids[ $i ] ];
		}
		self::workAroundZero( $subkey, $ids[ $lastSubkeyIndex ]);
		$subkey[ $ids[ $lastSubkeyIndex ] ] = &$tl;
		return true;
	}
}

TouhouPatchCenter::registerHook( 'csv', 'TPCFmtCSV::onCSV' );
