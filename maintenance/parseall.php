<?php

/**
  * Clears any existing mappings and rebuilds all patches.
  *
  * @file
  * @author Nmlgc
  */

use MediaWiki\MediaWikiServices;

require_once( dirname( __FILE__ ) . "/../../../maintenance/Maintenance.php" );

class TPCRebuild extends Maintenance {

	public function logAndEval( $i, $num, $pad, Title $title ) {
		if( !$title->exists() ) {
			return;
		}
		$this->output( sprintf(
			"\t(%{$pad}d/%{$pad}d) %s...\n",
			$i, $num, $title->getFullText()
		) );
		TouhouPatchCenter::evalTitle( $title );
	}

	public function parsePatchMap() {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$files = $dbr->select( 'tpc_patch_map', array( 'pm_namespace', 'pm_title' ) );
		$num = $files->numRows();
		$i = 1;
		$pad = preg_match_all( "/[0-9]/", $num );
		foreach ( $files as $file ) {
			$title = Title::makeTitle( $file->pm_namespace, $file->pm_title );
			$this->logAndEval( $i, $num, $pad, $title );
			$i++;
		}
	}

	public function parseTranslatablePages() {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$sourcePages = $dbr->select( 'tpc_tl_source_pages', '*' );
		$patches = $dbr->select( 'tpc_tl_patches', 'tl_code' );
		$num = ( $sourcePages->numRows() * $patches->numRows() );
		$i = 1;
		$pad = preg_match_all( "/[0-9]/", $num );
		foreach ( $sourcePages as $page ) {
			foreach ( $patches as $patch ) {
				$title = Title::makeTitle(
					$page->tlsp_namespace, "{$page->tlsp_title}/{$patch->tl_code}"
				);
				$this->logAndEval( $i, $num, $pad, $title );
				$i++;
			}
		}
	}

	public function rebuild() {
		TPCStorage::init();

		$this->output( "Copying all patch-mapped files…\n ");
		$this->parsePatchMap();

		$this->output( "Parsing all translatable pages…\n ");
		$this->parseTranslatablePages();
	}

	public function execute() {
		return $this->rebuild();
	}
}

$maintClass = 'TPCRebuild';
require_once( RUN_MAINTENANCE_IF_MAIN );
