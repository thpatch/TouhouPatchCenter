<?php

/**
  * Clears any existing mappings and rebuilds all patches.
  *
  * @file
  * @author Nmlgc
  */

require_once( dirname( __FILE__ ) . "/../../../maintenance/Maintenance.php" );

class TPCRebuild extends Maintenance {

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
		$this->output( "Parsing all translatable pages…\n ");
		$this->parseTranslatablePages();
	}

	public function execute() {
		return $this->rebuild();
	}
}

$maintClass = 'TPCRebuild';
require_once( RUN_MAINTENANCE_IF_MAIN );
