<?php

/**
  * Page inclusion handler.
  * Registers the following template hooks:
  *
  * {{thcrap_target}}
  * {{thcrap_include}}
  * {{thcrap_tl_include}}
  * {{thcrap_prefix_include}}
  * {{thcrap_prefix_file_include}} / {{thcrap_image}}
  */

use MediaWiki\MediaWikiServices;

class TPCInclude {

	protected static function getGame( &$tpcState, &$temp ) {
		return ( $temp->params['game'] ?? $tpcState->getCurGame() );
	}

	public static function getTitleFromLink( &$curTitle, $str, $namespace = NS_MAIN ) {
		// Subpage links
		if ( $str[0] === '/' ) {
			// Remove trailing slash
			if ( substr($str, -1) === '/') {
				$str = substr( $str, 1, -1 );
			} else {
				$str = substr( $str, 1 );
			}
			return $curTitle->getSubpage( $str );
		// External links
		} elseif ( $str[0] === ':' ) {
			// TODO: An entirely different matter...
			return null;
		// Full links
		} else {
			return Title::newFromText( $str, $namespace );
		}
	}

	public static function onTarget( &$tpcState, $title, &$temp, $patch = null ) {
		if ( !$title ) {
			return true;
		}
		$game = self::getGame( $tpcState, $temp );
		$file = ( $temp->params['file'] ?? $temp->params['target'] ?? null );

		if ( !$patch ) {
			$patch = $tpcState->patches[0];
		}

		TPCPatchMap::update( $title, $patch, $game, $file );
		if ( $title->getArticleID() ) {
			TouhouPatchCenter::evalTitle( $title );
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

	public static function onTLInclude( &$tpcState, &$title, &$temp ) {
		$page = $temp->params[1];
		$targetTitle = self::getTitleFromLink( $title, $page );

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$insert = array(
			'tlsp_namespace' => $targetTitle->getNamespace(),
			'tlsp_title' => $targetTitle->getText(),

			// This makes hypothetical support for non-Japanese games as easy as reading this value
			// from $temp->params[2] instead.
			'tlsp_code' => 'ja',
		);
		$dbw->insert( 'tpc_tl_source_pages', $insert, __METHOD__, 'IGNORE' );
		return true;
	}

	public static function onPrefixInclude( &$tpcState, &$title, &$temp ) {
		// NSML needs [ and ] in some filenames, which are illegal in MediaWiki
		// page names, so we use dashes instead.
		// This replaces them for the wiki file names only.
		$page = str_replace( array( '[', ']' ), '-', $temp->params[1] );

		$game = self::getGame( $tpcState, $temp );
		$namespace = intval( $temp->params['namespace'] ?? 0 );
		foreach ( TPCTLPatches::get() as $patch => $lang ) {
			$fullPage = "$patch-$game-$page";
			$targetTitle = self::getTitleFromLink( $title, $fullPage, $namespace );
			self::onTarget( $tpcState, $targetTitle, $temp, $patch );
		}
		return true;
	}

	public static function onPrefixFileInclude( &$tpcState, &$title, &$temp ) {
		$temp->params['namespace'] = NS_FILE;
		$temp->params[1] = preg_replace( '/\//', '-', $temp->params['target'] );
		return self::onPrefixInclude( $tpcState, $title, $temp );
	}
}

TouhouPatchCenter::registerHook( 'thcrap_target', 'TPCInclude::onTarget' );
TouhouPatchCenter::registerHook( 'thcrap_include', 'TPCInclude::onInclude' );
TouhouPatchCenter::registerHook( 'thcrap_tl_include', 'TPCInclude::onTLInclude' );
TouhouPatchCenter::registerHook( 'thcrap_prefix_include', 'TPCInclude::onPrefixInclude' );
TouhouPatchCenter::registerHook( 'thcrap_prefix_file_include', 'TPCInclude::onPrefixFileInclude' );
TouhouPatchCenter::registerHook( 'thcrap_image', 'TPCInclude::onPrefixFileInclude' );
