<?php

/**
  * Parsers for top-level information stuff.
  * Registers the following template hooks:
  *
  * {{thcrap_patch_info}}
  * {{thcrap_game_info}}
  *
  * @file
  * @author Nmlgc
  */

class TPCInfo {

	public static function onPatchInfo( &$tpcState, $title, $temp ) {
		$pageTitle = strtolower( $title->getDBKey() );

		$patchJS = &$tpcState->getFile( null, 'patch.js' );

		$patchJS['id'] = $pageTitle;

		$patchTitle = TPCUtil::dictGet( $temp->params['title'] );
		$patchJS['title'] = $patchTitle;

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

$wgTPCHooks['thcrap_patch_info'][] = 'TPCInfo::onPatchInfo';
