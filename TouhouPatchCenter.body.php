<?php

/**
  * Main, top-level parse hooks.
  *
  * @file
  * @author Nmlgc
  */

use MediaWiki\MediaWikiServices;
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

	/**
	  * @return array Array of Template objects
	  */
	public static function scrapeRestrictedTemplates( $content ) {
		if ( is_a( $content, 'TextContent' ) ) {
			$text = $content->getText();
			$temps = MWScrape::toArray( $text );
			return array_filter( $temps, "TouhouPatchCenter::isRestricted" );
		} else {
			return array();
		}
	}
	// ----------------------

	// wfLocalFile() is deprecated as of MediaWiki 1.37, and we're really supposed to replace it
	// with this piece of bloat. Indistinguishable from satire.
	protected static function resolveLocalFile( Title &$title ): ?LocalFile {
		return MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo()->newFile( $title );
	}

	// Why does MediaWiki even allow this case to exist?!
	protected static function isBothUploadAndRedirect(
		Title &$title, Content $content = null
	): bool {
		$localFile = self::resolveLocalFile( $title );
		if ( !$localFile ) {
			return false;
		}
		return ( $localFile->exists() && ( ( $content ?? $title )->isRedirect() ) );
	}

	public static function evalPage( Title &$title, $content = null ) {
		if ( TPCPatchMap::isPatchRootPage( $title ) ) {
			$tpcState = new TPCState( array( strtolower( $title->getDBKey() ) ), null, null );
		} else {
			$tpcState = TPCState::from( $title );
			if ( !$tpcState ) {
				return false;
			}
		}

		if ( !$content ) {
			$content = WikiPage::factory( $title )->getContent();
		}
		if ( !is_a( $content, 'TextContent' ) ) {
			return;
		}
		$text = $content->getText();
		$temps = MWScrape::toArray( $text );
		foreach ( $temps as $i ) {
			self::runTPCHooks( $i->name, $tpcState, $title, $i );
		}
		TPCStorage::writeState( $tpcState );
	}

	public static function evalFile( Title $fileTitle ) {
		if ( self::isBothUploadAndRedirect( $fileTitle ) ) {
			$title = $fileTitle->getPrefixedText();
			throw new MWException(
				"`$title` is both a redirect and an uploaded file. Delete one of those."
			);
		}

		// Could be a target page for a redirect, even if it's a redirect itself.
		$pages = array_merge( [ $fileTitle ], $fileTitle->getRedirectsHere() );

		if ( $fileTitle->isRedirect() ) {
			$content = WikiPage::factory( $fileTitle )->getContent();
			// Might still be `null` if the page isn't present in the database.
			if ( !$content ) {
				return;
			}
			$fileTitle = $content->getUltimateRedirectTarget();
		}
		$localFile = self::resolveLocalFile( $fileTitle );
		$filePath = $localFile->getLocalRefPath();
		if ( !$filePath ) {
			return self::deleteFiles( $pages );
		}

		foreach ( $pages as $i ) {
			if ( $tpcState = TPCState::from( $i ) ) {
				$target = ( $tpcState->getCurFile() ?? $i->getBaseText() );
				$tpcState->addCopy( $target, $filePath );
				TPCStorage::writeState( $tpcState );
			}
		}
	}

	protected static function evalTheme( Title $title ) {
		if ( !$title->isSubpage() ) {
			return false;
		}
		$tpcState = new TPCState( array( "lang_" . $title->getSubpageText() ), null, null );

		$id = strtr( $title->getBaseText(), ' ', '_' );
		TPCFmtTheme::onTheme( $tpcState, $title, $id );

		TPCStorage::writeState( $tpcState );
	}

	public static function evalTitle( Title $title, $content = null ) {
		// Yes, this is how the MediaWiki core differentiates, too.
		switch ( $title->getNamespace() ) {
			case NS_THEMEDB:
				return self::evalTheme( $title );
			case NS_FILE:
				return self::evalFile( $title );
			default:
				return self::evalPage( $title, $content );
		}
	}

	// Deletes $title, and all files that link to it.
	public static function deleteFilesAndRedirects( Title $title ) {
		self::deleteFiles( array_merge( [ $title ], $title->getRedirectsHere() ) );
	}

	// Deletes the given list of $pages.
	public static function deleteFiles( array $pages ) {
		foreach ( $pages as $i ) {
			if ( $tpcState = TPCState::from( $i ) ) {
				$target = ( $tpcState->getCurFile() ?? $i->getBaseText() );
				$tpcState->addDeletion( $target );
				TPCStorage::writeState( $tpcState );
			}
		}
	}

	// MediaWiki hooks
	// ---------------

	public static function onArticleDeleteAfterSuccess( Title $title, OutputPage $output ) {
		self::deleteFilesAndRedirects( $title );
	}

	public static function onMultiContentSave(
		MediaWiki\Revision\RenderedRevision $renderedRevision,
		User $user,
		CommentStoreComment $summary,
		$flags,
		Status $hookStatus
	) {
		$revision = $renderedRevision->getRevision();
		$page = $revision->getPage();
		$newContent = $revision->getContent( SlotRecord::MAIN );

		if ( self::isBothUploadAndRedirect( $page, $newContent ) ) {
			$hookStatus->fatal( 'tpc-file-redirect-overriding-upload', $page );
			return false;
		}
		if ( !$user->isAllowed( 'tpc-restricted' ) ) {
			// Does this edit add, remove or modify any restricted templates?
			// (Yes, we need the count comparison because array_udiff() doesn't
			// seem to take newly added templates into account.)
			$oldPage = WikiPage::factory( $page );
			$newRTs = self::scrapeRestrictedTemplates( $newContent );
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

	public static function onPageContentLanguage( Title $title, &$pageLang, $userLang ) {
		$namespace = $title->getNamespace();
		if ( TPCPatchMap::isTLIncludedPage( $namespace, $title->getText() ) ) {
			$pageLang = Language::factory( TPCUtil::getNamespaceBaseLanguage( $namespace ) );
		}
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
		self::deleteFilesAndRedirects( $file->getTitle() );
		return true;
	}

	public static function onTitleMoveComplete(
		Title &$title, Title &$newtitle, User &$user, $oldid, $newid
	) {
		self::evalTitle( $newtitle );
		return true;
	}

	public static function onUploadVerifyFile( $upload, $mime, &$error ) {
		$title = $upload->getTitle();
		if ( $title->isRedirect() ) {
			$error = new ApiMessage(
				[ 'tpc-file-upload-overriding-redirect', $title->getPrefixedText() ]
			);
			return;
		}
		$error = true;
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
