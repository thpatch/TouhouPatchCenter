<?php

/**
  * Database-powered page->patch mapping.
  *
  * @file
  * @author Nmlgc
  */

use MediaWiki\MediaWikiServices;

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

	/**
	  * @return bool `true` if the given page is part of `tpc_tl_source_pages`.
	  */
	public static function isTLIncludedPage( int $namespace, string $title ): bool {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		return $dbr->selectRow( 'tpc_tl_source_pages', 1, array(
			'tlsp_namespace' => $namespace,
			'tlsp_title' => $title
		)) !== false;
	}

	/**
	  * @return array Mapping information for this page.
	  * See tpc_patch_map.sql for the format.
	  */
	public static function buildTLMapping( string $game, string $lang ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$tl = $dbr->selectRow( 'tpc_tl_patches', 'tl_patch', array( 'tl_code' => $lang ) );
		if ( $tl === false ) {
			return null;
		}
		return ( object )array(
			'pm_patch' => array( $tl->tl_patch ),
			'pm_game' => $game,
			'pm_target' => null, // Important for TPCState::from()
		);
	}

	protected static function getMapping( Title &$title ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		// Get old value
		$map = $dbr->selectRow( 'tpc_patch_map', '*', array(
			'pm_namespace' => $title->getNamespace(),
			'pm_title' => $title->getText(),
		) );
		if ( $map ) {
			$map->pm_patch = explode( "\n", $map->pm_patch );
		}
		return $map;
	}

	public static function update( $title, $patch, $game = null, $target = null ) {
		$map = self::get( $title );
		$patches = self::mergePatch( $map, $patch );
		// If we already have a mapping, update only if necessary
		if (
			( $map ) and
			( in_array( $patch, $map->pm_patch ) ) and
			( $map->pm_game == $game ) and
			( $map->pm_target == $target )
		) {
			return;
		}

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
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

		$ret = self::getMapping( $title );
		if ( $ret or $namespace == NS_FILE ) {
			return $ret;
		}

		// Auto-generate the mapping from the page title. This has to work for the following cases:
		//
		// | Case                       | $title         | tl_source_page | Mapped game / patch |
		// |----------------------------+----------------+----------------+---------------------|
		// | Multi-game, source page    | Game titles    | Game titles    |     "", "lang_ja"   |
		// | Multi-game, translated     | Game titles/en | Game titles    |     "", "lang_en"   |
		// | Specific game, source page | Th06/Images    | Th06/Images    | "th06", "lang_ja"   |
		// | Specific game, translated  | Th06/Images/en | Th06/Images    | "th06", "lang_en"   |

		$root = $title->getRootText(); // Might indicate a game

		// Source page?
		if ( self::isTLIncludedPage( $namespace, $title->getText() ) ) {
			$game = $title->isSubpage() ? lcfirst( $root ) : "";
			return self::buildTLMapping( $game, TPCUtil::getNamespaceBaseLanguage( $namespace ) );
		}

		// If $title is a translated page, getBaseText() gives us the source page…
		$base = $title->getBaseText();

		// … which we can check against the same database table to check if this is a translated
		// page of a registered source page.
		if ( self::isTLIncludedPage( $namespace, $base ) ) {
			$game = ( $root != $base ) ? lcfirst( $root ) : "";
			return self::buildTLMapping( $game, $title->getSubpageText() );
		}
		return null;
	}

	/**
	  * @param Title $title Title object
	  * @return bool
	  */
	public static function isPatchRootPage( $title ) {
		return ( $title->getNamespace() === NS_PATCH and !$title->isSubpage() );
	}

	public static function getPatchRootPages() {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		return $dbr->select(
			'page',
			array( 'page_namespace', 'page_title'),
			array(
				'page_namespace' => NS_PATCH,
				"page_title NOT LIKE '%/%'"
			)
		);
	}
}
