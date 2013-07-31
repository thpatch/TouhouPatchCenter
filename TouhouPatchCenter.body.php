<?php

/**
  * Main, top-level parse hooks.
  *
  * @file
  * @author Nmlgc
  */

class TouhouPatchCenter {

	protected static function runHooks( $hook, &$hookArray, $params ) {
		$hook = TPCUtil::normalizeHook( $hook );
		if ( isset( $hookArray[$hook] ) ) {
			foreach ( $hookArray[$hook] as $func) {
				return call_user_func_array( $func, $params );
			}
		} else {
			return false;
		}
	}

	public static function runTPCHooks( $hook, &$tpcState, &$title, &$temp ) {
		global $wgTPCHooks;
		return self::runHooks( $hook, $wgTPCHooks, array( &$tpcState, &$title, &$temp ) );
	}

	public static function evalContent( TPCState &$tpcState, Title $title, Content &$content ) {
		$text = $content->getNativeData();
		$temps = MWScrape::toArray($text);
		foreach ( $temps as $i ) {
			self::runTPCHooks( $i->name, $tpcState, $title, $i );
		}
	}

	public static function evalPage( Title &$title, $content = null ) {
		$tpcState = new TPCState;
		if ( $tpcState->init( $title ) ) {
			if ( !$content ) {
				$newPage = WikiPage::factory( $title );
				$content = $newPage->getContent();
			}
			self::evalContent( $tpcState, $title, $content );
			TPCStorage::writeState( $tpcState );
		}
	}

	public static function evalFile( Title $fileTitle ) {
		$pages = array( $fileTitle );
		if ( $fileTitle->isRedirect() ) {
			$content = WikiPage::factory( $fileTitle )->getContent();
			$fileTitle = $content->getUltimateRedirectTarget();
		} else {
			// could be a target page for a redirect
			$pages = array_merge( $fileTitle->getRedirectsHere(), $pages );
		}
		$localFile = wfLocalFile( $fileTitle );
		$filePath = $localFile->getLocalRefPath();
		if ( !$filePath ) {
			return;
		}

		foreach ( $pages as $i ) {
			// Is this file already mapped to a patch?
			$map = TPCPatchMap::get( $i );
			if ( !$map or !$map->pm_patch ) {
				// Nope, nothing we care about
				continue;
			}
			$tpcState = new TPCState;
			$tpcState->patches = $map->pm_patch;
			$tpcState->switchGame( $map->pm_game );
			$target = TPCUtil::dictGet( $map->pm_target, $i->getBaseText() );
			$tpcState->addCopy( $target, $filePath );
			TPCStorage::writeState( $tpcState );
		}
	}

	public static function evalTitle( Title $title, $content = null ) {
		self::evalPage( $title, $content );

		// Yes, this is how the MediaWiki core differentiates, too.
		if ( $title->getNamespace() === NS_FILE ) {
			self::evalFile( $title );
		}
	}

	// =====
	// Hooks
	// =====
	public static function onPageSave(
		$article, $user, $content, $summary, $isMinor,
		$isWatch, $section, $flags, $revision, $status, $baseRevId
	) {
		$title = $article->getTitle();
		if ( TPCPatchMap::isPatchRootPage( $title ) or TPCPatchMap::get( $title ) ) {
			self::evalTitle( $title, $content );
		}
		return true;
	}

	public static function onFileUpload( $file ) {
		self::evalFile( $file->getTitle() );
		return true;
	}

	public static function onTitleMoveComplete(
		Title &$title, Title &$newtitle, User &$user, $oldid, $newid
	) {
		self::evalTitle( $newtitle );
		return true;
	}

	public static function onCanonicalNamespaces( &$list )	{
		global $wgTPCPatchNamespace;
		global $wgNamespacesWithSubpages;
		if ( !defined( 'NS_PATCH' ) ) {
			define( 'NS_PATCH', $wgTPCPatchNamespace );
			define( 'NS_PATCH_TALK', $wgTPCPatchNamespace + 1 );
		}
		$list[NS_PATCH] = 'Patch';
		$list[NS_PATCH_TALK] = 'Patch_talk';
		$wgNamespacesWithSubpages[NS_PATCH] = 1;
		$wgNamespacesWithSubpages[NS_PATCH_TALK] = 1;
		return true;
	}

	public static function onDatabaseUpdate( DatabaseUpdater $updater ) {
		$dir = __DIR__;
		$updater->addExtensionTable( 'tpc_patch_map', "$dir/tpc_patch_map.sql" );
		$updater->addExtensionIndex( 'tpc_patch_map', 'tpc_pm_lookup', "$dir/tpc_pm_lookup.sql" );
		$updater->addExtensionTable( 'tpc_tl_patches', "$dir/tpc_tl_patches.sql" );
		return true;
	}
	// =====
}
