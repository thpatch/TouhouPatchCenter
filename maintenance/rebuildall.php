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

	// ------------ lixlpixel recursive PHP functions -------------
	// recursiveRemoveDirectory( directory to delete, empty )
	// expects path to directory and optional TRUE / FALSE to empty
	// ------------------------------------------------------------
	function recursiveRemoveDirectory( $directory, $empty = FALSE )
	{
		if ( substr( $directory, -1 ) == '/' ) {
			$directory = substr( $directory, 0, -1 );
		}
		if ( !file_exists ( $directory ) || !is_dir ( $directory ) ) {
			return FALSE;
		} elseif(is_readable($directory)) {
			$handle = opendir($directory);
			while ( FALSE !== ( $item = readdir( $handle ) ) ) {
				if($item != '.' && $item != '..') 	{
					$path = $directory . '/' . $item;
					if ( is_dir ( $path ) ) {
						$this->recursiveRemoveDirectory( $path );
					} else {
						unlink( $path );
					}
				}
			}
			closedir( $handle );
			if ( $empty == FALSE ) {
				if ( !rmdir( $directory ) ) {
					return FALSE;
				}
			}
		}
		return TRUE;
	}
	// ------------------------------------------------------------

	public function clearMappings() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'tpc_patch_map', '*' );
	}

	public function clearServers( &$servers ) {
		foreach ( $servers as $i ) {
			$path = TPCServer::getServerPath( $i );
			$this->recursiveRemoveDirectory( $path, true );
		}
	}

	public function parseAllPatches() {
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
		global $wgTPCServers;

		$this->output( "Clearing page->patch mappings in database...\n" );
		$this->clearMappings();

		if ( $wgTPCServers ) {
			$this->output( "Removing all files on all servers...\n" );
			$this->clearServers( $wgTPCServers );
		} else {
			// $this->output
		}

		$this->output( "Parsing all patch pages...\n" );
		$this->parseAllPatches();
	}

	public function execute() {
		return $this->rebuild();
	}
}

$maintClass = 'TPCRebuild';
require_once( RUN_MAINTENANCE_IF_MAIN );
