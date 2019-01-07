<?php

/**
  * Database-powered page->patch mapping.
  *
  * @file
  * @author Nmlgc
  */

class TPCPatchMap {

	protected static function mergePatch( &$map, &$patch ) {
		// If we already have a mapping, update only if necessary
		if ( !$map or in_array( $patch, $map->pm_patch ) ) {
			return $patch;
		} else {
			$map->pm_patch[] = $patch;
			return implode( "\n", $map->pm_patch );
		}
	}

	// -------------------------
	// Database access functions
	// -------------------------
	protected static function getMapping( $table, $vars, $conds ) {
		$dbr = wfGetDB( DB_SLAVE );
		// Get old value
		$query = $dbr->select( $table, $vars, $conds );
		$map = $query->fetchObject();
		if ( $map ) {
			$map->pm_patch = explode( "\n", $map->pm_patch );
		}
		return $map;
	}

	protected static function updateMapping( &$title, &$patch, &$game, &$target ) {
		$dbw = wfGetDB( DB_MASTER );
		$inserts = array(
			'pm_namespace' => $title->getNamespace(),
			'pm_title' => $title->getText(),
			'pm_patch' => $patch,
			'pm_game' => $game,
			'pm_target' => $target
		);
		$dbw->replace( 'tpc_patch_map', null, $inserts );
	}
	// -------------------------

	/**
	  * @return array Mapping information for this page.
	  * See tpc_patch_map.sql for the format.
	  */
	public static function get( $title ) {
		$namespace = $title->getNamespace();
		$ret = self::getMapping(
			'tpc_patch_map',
			'*',
			array(
				'pm_namespace' => $namespace,
				'pm_title' => $title->getText()
			)
		);
		if ( $ret or $namespace == NS_FILE ) {
			return $ret;
		}
		// Not actually a gross hack, Title::isSubpage() works just like this.
		$subpageLevels = substr_count( $title->getText(), "/" );
		$code = ( $subpageLevels >= 1 )
			? $title->getSubpageText()
			: TPCUtil::getNamespaceBaseLanguage( $namespace );
		$game = ( $subpageLevels >= 2 ) ? lcfirst( $title->getRootText() ) : "";

		$dbr = wfGetDB( DB_SLAVE );
		$patchForLang = $dbr->select( 'tpc_tl_patches', 'tl_patch', array(
			'tl_code' => $code
		) )->fetchObject();
		if ( !$patchForLang ) {
			return null;
		}
		return ( object )array(
			'pm_patch' => array( $patchForLang->tl_patch ),
			'pm_game' => $game
		);
	}

	/**
	  * @return bool true if mapping was updated
	  */
	public static function update( $title, $patch, $game = null, $target = null ) {
		$map = self::get( $title );
		$patches = self::mergePatch( $map, $patch );
		// If we already have a mapping, update only if necessary
		if (
			( $map ) and
			( in_array( $patch, $map->pm_patch ) ) and
			( TPCUtil::dictGet( $map->pm_game ) === $game ) and
			( TPCUtil::dictGet( $map->pm_target ) === $target )
		) {
				return false;
		}
		self::updateMapping( $title, $patches, $game, $target );
		return true;
	}

	/**
	  * @param Title $title Title object
	  * @return bool
	  */
	public static function isPatchRootPage( $title ) {
		global $wgTPCPatchNamespace;
		return ( $title->getNamespace() === $wgTPCPatchNamespace and !$title->isSubpage() );
	}

	public static function isPatchPage( $title ) {
		return self::isPatchRootPage( $title ) or self::get( $title );
	}

	public static function getPatchRootPages() {
		global $wgTPCPatchNamespace;
		$dbr = wfGetDB( DB_SLAVE );
		return $dbr->select(
			'page',
			array( 'page_namespace', 'page_title'),
			array(
				'page_namespace' => $wgTPCPatchNamespace,
				"page_title NOT LIKE '%/%'"
			)
		);
	}
}
