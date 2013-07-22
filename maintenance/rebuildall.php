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

	public function rebuild() {
		TPCStorage::init();
		$this->output( "Clearing page->patch mappings in database...\n" );
		$this->clearMappings();

		$this->output( "Removing all files on all servers...\n" );
		TPCStorage::wipe();

		$this->output( "Parsing all patch pages...\n" );
		$this->parsePatches();
	}

	public function execute() {
		return $this->rebuild();
	}
}

$maintClass = 'TPCRebuild';
require_once( RUN_MAINTENANCE_IF_MAIN );
