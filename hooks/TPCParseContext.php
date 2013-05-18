<?php 

/**
  * Context switching hooks.
  * Registers the following template hooks:
  *
  * {{thcrap_game}}
  * {{thcrap_data_patch}}
  *
  * @file
  * @author Nmlgc
  */

class TPCParseContext {

	static public function onGame( &$tpcState, $title, $temp ) {
		return $tpcState->switchGame( $temp->params[1] );
	}

	static public function onPatchFile( &$tpcState, $title, $temp ) {
		return $tpcState->switchDataFilePatch( $temp->params[1] );
	}
}

$wgTPCHooks['thcrap_game'][] = 'TPCParseContext::onGame';
$wgTPCHooks['thcrap_patch_file'][] = 'TPCParseContext::onPatchFile';

