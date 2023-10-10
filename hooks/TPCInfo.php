<?php

/**
  * Parsers for top-level information stuff.
  * Registers the following template hooks:
  *
  * {{thcrap_patch_info}}
  *
  * @file
  * @author Nmlgc
  */

class TPCInfo {

	public static function onPatchInfo( &$tpcState, $title, $temp ) {
		$pageTitle = strtolower( $title->getDBKey() );

		$patchJS = &$tpcState->getFile( null, 'patch.js' );

		$patchJS['id'] = $pageTitle;

		if ( isset( $temp->params['title'] ) ) {
			$patchJS['title'] = $temp->params['title'];
		}

		if ( isset( $temp->params['min_build'] ) ) {
			global $wgTPCRepoEngineURL;
			$patchJS['min_build'] = $temp->params['min_build'];
			if ( $wgTPCRepoEngineURL ) {
				$patchJS['url_engine'] = $wgTPCRepoEngineURL;
			}
		}
		if ( isset( $temp->params['dependencies'] ) ) {
			$vars = TPCParse::parseCSV( $temp->params['dependencies'] );
			// ----
			// Workaround for a bug in 2014-01-27, remove once the next build is out!
			// ----
			if ( gettype( $vars ) === 'string' ) {
				$vars = [$vars];
			}
			// ----
			$patchJS['dependencies'] = $vars;
		}
		return true;
	}
}

TouhouPatchCenter::registerHook( 'thcrap_patch_info', 'TPCInfo::onPatchInfo' );
