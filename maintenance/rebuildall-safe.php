<?php

/**
  * Clears any existing mappings and parsed patch files on the servers
  * and rebuilds all patches.
  *
  * @file
  * @author Nmlgc
  */

require_once( dirname( __FILE__ ) . "/../../../maintenance/Maintenance.php" );

class TPCRebuild extends Maintenance {

	public function clearMappings() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'tpc_patch_map', '*' );
		$dbw->delete( 'tpc_tl_patches', '*' );
	}

	public function parsePatches() {
		$patches = TPCPatchMap::getPatchRootPages();
		$num = $patches->numRows();
		$i = 1;
		$pad = preg_match_all( "/[0-9]/", $num );
		foreach ( $patches as $patch ) {
			$title = Title::makeTitle( $patch->page_namespace, $patch->page_title );

			$out = sprintf(
				"\t(%{$pad}d/%{$pad}d) %s...\n",
				$i, $num, $title->getFullText()
			);
			$this->output( $out );

			TouhouPatchCenter::evalPage( $title );
			$i++;
		}
	}

	public function parseTranslatablePages() {
		$dbr = wfGetDB( DB_SLAVE );
		$pages = $dbr->select(
			'tpc_patch_map',
			array( 'pm_namespace', 'pm_title' ),
			array(
				'pm_namespace' => 0,
				"pm_title LIKE '%/%'"
			)
		);
		$num = $pages->numRows();
		$i = 1;
		$pad = preg_match_all( "/[0-9]/", $num );
		foreach ( $pages as $page ) {
			$title = Title::makeTitle( $page->pm_namespace, $page->pm_title );

			if( $title->exists() ) {
				$out = sprintf(
					"\t(%{$pad}d/%{$pad}d) %s...\n",
					$i, $num, $title->getFullText()
				);
				$this->output( $out );

				TouhouPatchCenter::evalTitle( $title );
			}
			$i++;
		}
	}

	public function rebuild() {
		TPCStorage::init();
/*
		$this->output( "Clearing page->patch mappings in database...\n" );
		$this->clearMappings();
		
		$this->output( "Removing all files on all servers...\n" );
		TPCStorage::wipe();

		$this->output( "Parsing all patch pages...\n" );
		$this->parsePatches();
*/
		$this->output( "Parsing all translatable pages…\n ");
		$this->parseTranslatablePages();
	}

	public function execute() {
		return $this->rebuild();
	}
}

$maintClass = 'TPCRebuild';
require_once( RUN_MAINTENANCE_IF_MAIN );
