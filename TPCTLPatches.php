<?php

/**
  * Database-powered list of translation patches.
  *
  * @file
  * @author Nmlgc
  */

class TPCTLPatches {

	// All mappings that have been written to or read from the database.
	protected static $patches = array();

	public static function get() {
		if ( !empty( self::$patches ) ) {
			return self::$patches;
		}
		$dbr = wfGetDB( DB_SLAVE );
		$query = $dbr->select( 'tpc_tl_patches', "*" );
		$ret = array();
		foreach ( $query as $q ) {
			$ret[$q->tl_patch] = $q->tl_code;
		}
		self::$patches = $ret;
		return $ret;
	}

	public static function update( $mappings ) {
		self::$patches = array_merge( self::$patches, $mappings );
		$dbw = wfGetDB( DB_MASTER );
		$rows = array();
		foreach ( $mappings as $patch => $lang ) {
			$rows[] = array(
				'tl_patch' => $patch,
				'tl_code' => $lang
			);
		}
		$dbw->replace( 'tpc_tl_patches', null, $rows );
		return true;
	}

	public static function onTLPatches( &$tpcState, &$title, &$temp ) {
		return self::update( $temp->params );
	}
}

$wgTPCHooks['thcrap_tl_patches'][] = 'TPCTLPatches::onTLPatches';
$wgTPCRestrictedTemplates[] = 'thcrap_tl_patches';
