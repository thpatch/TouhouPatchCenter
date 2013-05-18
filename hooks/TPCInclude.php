<?php

/**
  * Page inclusion handler.
  * Registers the following template hooks:
  *
  * {{thcrap_target}}
  * {{thcrap_include}}
  * {{thcrap_tl_patches}}
  * {{thcrap_tl_include}}
  */

class TPCInclude {

	public static function getTitleFromLink( &$curTitle, $str ) {
		// Subpage links
		if ( $str[0] === '/' ) {
			// Remove trailing slash
			if ( substr($str, -1) === '/') {	
				$str = substr($str, 1, -1);
			} else {
				$str = substr($str, 1);
			}
			return $curTitle->getSubpage( $str );
		// External links
		} elseif ( $str[0] === ':' ) {
			// TODO: An entirely different matter...
			return null;
		// Full links
		} else {
			return Title::newFromText( $str );
		}
	}

	public static function onTarget( &$tpcState, $title, $temp, $patch = null ) {
		if ( !$title ) {
			return true;
		}
		$game = TPCUtil::dictGet( $temp->params['game'] );
		if ( !$game ) {
			$game = $tpcState->getCurGame();
		}
		$file = TPCUtil::dictGet( $temp->params['file'] );
		if ( !$file ) {
			$file = TPCUtil::dictGet( $temp->params['target'] );
		}

		$titleID = $title->getArticleID();

		if ( !$patch ) {
			$patch = $tpcState->patches[0];
		}

		$parse = TPCPatchMap::update( $title, $patch, $game, $file );
		if ( $parse and $titleID ) {
			// Yes, this is how the MediaWiki core differentiates, too.
			if ( $title->getNamespace() === NS_FILE ) {
				TouhouPatchCenter::evalFile( $title );
			} else {
				TouhouPatchCenter::evalPage( $title );
			}
		}
		return true;

	}
	/**
	  * @param TPCState $tpcState Parse state
	  * @param Title $title Title object of the page that called this template
	  * @param Template $temp Template object to evaluate
	  */
	public static function onInclude( &$tpcState, &$title, &$temp ) {
		$page = $temp->params[1];
		$targetTitle = self::getTitleFromLink( $title, $page );
		return self::onTarget( $tpcState, $targetTitle, $temp );
	}

	public static function onTLPatches( &$tpcState, &$title, &$temp ) {
		$tpcState->tlPatches = $temp->params;
		return true;
	}

	public static function onTLInclude( &$tpcState, &$title, &$temp ) {
		if ( !isset( $tpcState->tlPatches ) ) {
			return true;
		}
		$page = $temp->params[1];
		// TODO: This is really slow... Refactor into something that writes
		// all languages in a single database call.
		foreach ( $tpcState->tlPatches as $patch => $lang ) {
			$targetTitle = self::getTitleFromLink( $title, $page . "/$lang" );
			self::onTarget( $tpcState, $targetTitle, $temp, $patch );
		}
		return true;
	}
}

$wgTPCHooks['thcrap_target'][] = 'TPCInclude::onTarget';
$wgTPCHooks['thcrap_include'][] = 'TPCInclude::onInclude';
$wgTPCHooks['thcrap_tl_patches'][] = 'TPCInclude::onTLPatches';
$wgTPCHooks['thcrap_tl_include'][] = 'TPCInclude::onTLInclude';
