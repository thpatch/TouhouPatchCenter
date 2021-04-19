<?php

/**
  * Parser for Music Room comments.
  * Registers the following template hooks:
  *
  * {{thcrap_music}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtMusic {

	public static function onMusic( &$tpcState, &$title, &$temp ) {
		$num = TPCUtil::dictGet( $temp->params['num'] );
		$tl = TPCUtil::dictGet( $temp->params['tl'] );
		$game = $tpcState->getCurGame();
		if ( empty( $num ) or empty( $game ) ) {
			return true;
		}
		$id = sprintf( '%s_%02d', $game, $num );

		$lang = $title->getPageLanguage()->getCode();
		$idTitle = Title::newFromText( "$id/$lang" , NS_THEMEDB );
		// Needs to be called first, because any getFile() or switchFile()
		// call that gives or implies curGame == null resets the game
		// information in the state!
		TPCFmtTheme::onTheme( $tpcState, $idTitle, $id );

		$musiccmt = &$tpcState->getFile( $game, "musiccmt.js" );

		$tl = TPCParse::parseLines( $tl );
		foreach ( $tl as $i ) {
			$test = trim( $i, " 　@" );
			if ( $test !== "" ) {
				$musiccmt[$num] = $tl;
				break;
			}
		}
		return true;
	}
}
TouhouPatchCenter::registerHook( 'thcrap_music', 'TPCFmtMusic::onMusic' );
// Short versions
TouhouPatchCenter::registerHook( 'musicroom', 'TPCFmtMusic::onMusic' );
