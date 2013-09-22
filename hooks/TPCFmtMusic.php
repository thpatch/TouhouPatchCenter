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

		// Need to be both called unconditionally in this order, because any
		// getFile or switchFile call that gives or implies curGame == null
		// resets the game information in the state!
		$themes = &$tpcState->getFile( null, "themes.js" );
		$musiccmt = &$tpcState->getFile( $game, "musiccmt.js" );

		// Fetch and store music title, resolving any redirects in the process
		$lang = $title->getPageLanguage()->getCode();
		$idTitle = Title::newFromText( "$id/$lang" , NS_THEMEDB );
		do {
			$idPage = WikiPage::factory( $idTitle );
			$idContent = $idPage->getContent();
		} while ( $idContent and $idTitle = $idContent->getRedirectTarget() );
		$idText = $idPage->getText();
		if ( $idText ) {
			$themes[$id] = $idText;
		}

		$tl = TPCParse::parseLines( $tl );
		foreach ( $tl as $i ) {
			$test = trim( $i, " 　@" );
			if ( !empty( $test ) ) {
				$musiccmt[$num] = $tl;
				break;
			}
		}
		return true;
	}
}
$wgTPCHooks['thcrap_music'][] = 'TPCFmtMusic::onMusic';
// Short versions
$wgTPCHooks['musicroom'][] = 'TPCFmtMusic::onMusic';
