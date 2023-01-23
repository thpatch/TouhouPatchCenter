<?php

/**
  * Context switching hooks.
  * Registers the following template hooks:
  *
  * {{thcrap_game}}
  * {{thcrap_game_file}}
  * {{thcrap_patch_file}}
  *
  * @file
  * @author Nmlgc
  */

class TPCParseContext {

	static public function onGame( &$tpcState, $title, $temp ) {
		return $tpcState->switchGame( $temp->params[1] );
	}

	static public function onGameFile( &$tpcState, $title, $temp ) {
		return $tpcState->switchGameFile( $temp->params[1] );
	}

	static public function onPatchFile( &$tpcState, $title, $temp ) {
		return $tpcState->switchGameFilePatch( $temp->params[1] );
	}
}

TouhouPatchCenter::registerHook( 'thcrap_game', 'TPCParseContext::onGame' );
TouhouPatchCenter::registerHook( 'thcrap_game_file', 'TPCParseContext::onGameFile' );
TouhouPatchCenter::registerHook( 'thcrap_patch_file', 'TPCParseContext::onPatchFile' );

