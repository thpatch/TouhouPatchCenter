<?php

/**
  * Main, top-level parse hooks.
  *
  * @file
  * @author Nmlgc
  */

use MediaWiki\Revision\SlotRecord;

class TouhouPatchCenter {

	// Patch generation hooks
	// ----------------------

	// Maps TPC template names to their patch generation functions.
	static protected $tpcHooks = [];

	// Array of restricted templates. A user needs the tpc-restricted right in order to save
	// edits that modify any of these templates on a page.
	static protected $restrictedTemplateNames = [];

	public static function registerHook( $templateName, $func ) {
		self::$tpcHooks[$templateName][] = $func;
	}

	// More readable than an optional parameter to registerHook().
	public static function registerRestrictedHook( $templateName, $func ) {
		self::registerHook( $templateName, $func );
		self::$restrictedTemplateNames[] = $templateName;
	}

	public static function getRestrictedTemplateNames() {
		return self::$restrictedTemplateNames;
	}

	public static function runTPCHooks( $hook, &$tpcState, &$title, &$temp ) {
		$params = array( &$tpcState, &$title, &$temp );
		$hook = TPCUtil::normalizeHook( $hook );
		if ( isset( self::$tpcHooks[$hook] ) ) {
			foreach ( self::$tpcHooks[$hook] as $func ) {
				return call_user_func_array( $func, $params );
			}
		} else {
			return false;
		}
	}

	public static function isRestricted( $temp ) {
		$hook = TPCUtil::normalizeHook( $temp->name );
		return in_array( $hook, self::$restrictedTemplateNames );
	}

	public static function scrapeRestrictedTemplates( $content ) {
		if ( is_a( $content, 'Content' ) ) {
			$text = $content->getNativeData();
			$temps = MWScrape::toArray( $text );
			return array_filter( $temps, "TouhouPatchCenter::isRestricted" );
		} else {
			return null;
		}
	}
	// ----------------------

	public static function evalContent( TPCState &$tpcState, Title $title, Content &$content ) {
		if ( $title->getNamespace() === NS_THEMEDB ) {
			$id = strtr( $title->getBaseText(), ' ', '_' );
			return TPCFmtTheme::onTheme( $tpcState, $title, $id );
		}
		$text = $content->getNativeData();
		$temps = MWScrape::toArray( $text );
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
				// Might still be `null` if the page isn't present in the database.
				if( !$content ) {
					return;
				}
			}
			self::evalContent( $tpcState, $title, $content );
			TPCStorage::writeState( $tpcState );
		}
	}

	public static function evalFile( Title $fileTitle ) {
		$pages = array( $fileTitle );
		if ( $fileTitle->isRedirect() ) {
			$content = WikiPage::factory( $fileTitle )->getContent();
			// Might still be `null` if the page isn't present in the database.
			if ( !$content ) {
				return;
			}
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
		// Yes, this is how the MediaWiki core differentiates, too.
		if ( $title->getNamespace() === NS_FILE ) {
			self::evalFile( $title );
		} else {
			self::evalPage( $title, $content );
		}
	}

	// MediaWiki hooks
	// ---------------

	public static function onMultiContentSave(
		MediaWiki\Revision\RenderedRevision $renderedRevision,
		User $user,
		CommentStoreComment $summary,
		$flags,
		Status $hookStatus
	) {
		if ( !$user->isAllowed( 'tpc-restricted' ) ) {
			// Does this edit add, remove or modify any restricted templates?
			// (Yes, we need the count comparison because array_udiff() doesn't
			// seem to take newly added templates into account.)
			$revision = $renderedRevision->getRevision();
			$oldPage = WikiPage::factory( $revision->getPage() );
			$newRTs = self::scrapeRestrictedTemplates( $revision->getContent( SlotRecord::MAIN ) );
			$oldRTs = self::scrapeRestrictedTemplates( $oldPage->getContent() );

			if ( count( $newRTs ) == count( $oldRTs ) ) {
				$diff = array_udiff( $oldRTs, $newRTs, "Template::differs" );
				if ( empty( $diff ) ) {
					return true;
				}
			}
			$hookStatus->fatal( 'tpc-edit-blocked' );
			return false;
		}
		return true;
	}

	public static function onPageSaveComplete(
		WikiPage $wikiPage,
		MediaWiki\User\UserIdentity $user,
		string $summary,
		int $flags,
		MediaWiki\Revision\RevisionRecord $revisionRecord,
		MediaWiki\Storage\EditResult $editResult
	) {
		self::evalTitle( $wikiPage->getTitle(), $wikiPage->getContent() );
		return true;
	}

	public static function onFileUpload( $file ) {
		self::evalFile( $file->getTitle() );
		return true;
	}

	public static function onFileDeleteComplete(
		$file, $oldimage, $article, $user, $reason
	) {
		if ( $oldimage ) {
			return true;
		}
		$title = $file->getTitle();
		$map = TPCPatchMap::get( $title );
		if ( !$map or !$map->pm_patch ) {
			return true;
		}
		$tpcState = new TPCState;
		$tpcState->patches = $map->pm_patch;
		$tpcState->switchGame( $map->pm_game );
		$target = TPCUtil::dictGet( $map->pm_target, $title->getBaseText() );
		$tpcState->addDeletion( $target );
		TPCStorage::writeState( $tpcState );
		return true;
	}

	public static function onTitleMoveComplete(
		Title &$title, Title &$newtitle, User &$user, $oldid, $newid
	) {
		self::evalTitle( $newtitle );
		return true;
	}

	public static function onCanonicalNamespaces( &$list ) {
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
		$updater->addExtensionTable( 'tpc_tl_patches', "$dir/tpc_tl_patches.sql" );
		$updater->addExtensionTable( 'tpc_tl_source_pages', "$dir/tpc_tl_source_pages.sql" );
		return true;
	}
	// ---------------

	public static function clearDatabase() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'tpc_patch_map', '*' );
		$dbw->delete( 'tpc_tl_patches', '*' );
		$dbw->delete( 'tpc_tl_source_pages', '*' );
	}

	public static function setup() {
		$dir = __DIR__ . "/hooks";
		require_once("$dir/TPCBinhack.php");
		require_once("$dir/TPCBreakpoint.php");
		require_once("$dir/TPCInclude.php");
		require_once("$dir/TPCInfo.php");
		require_once("$dir/TPCParseContext.php");
		require_once("$dir/TPCVersions.php");

		require_once("$dir/TPCFmtCSV.php");
		require_once("$dir/TPCFmtGentext.php");
		require_once("$dir/TPCFmtMissions.php");
		require_once("$dir/TPCFmtMsg.php");
		require_once("$dir/TPCFmtMusic.php");
		require_once("$dir/TPCFmtSpells.php");
		require_once("$dir/TPCFmtStrings.php");
		require_once("$dir/TPCFmtTheme.php");

		require_once("$dir/TPCFmtTasofro.php");

		require_once("$dir/TPCTLPatches.php");
	}
}
