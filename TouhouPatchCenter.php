<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

/**
  * Entry point for the Touhou Patch Center extension.
  *
  * @file
  * @author Nmlgc
  */

// Credits
$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'Touhou Patch Center',
	'author'         => 'Nmlgc',
	'descriptionmsg' => 'tpc-desc',
	'url'            => 'https://github.com/nmlgc/TouhouPatchCenter',
);

// Includes
// --------
$dir = __DIR__;

// Packages included by Composer, and our classes that need them
if ( is_readable( "$dir/vendor/autoload.php" ) ) {
	require_once("$dir/vendor/autoload.php");

	$wgAutoloadClasses['TPCServerSFTP'] = "$dir/TPCServerSFTP.php";
}

$wgAutoloadClasses['TouhouPatchCenter'] = "$dir/TouhouPatchCenter.body.php";
$wgAutoloadClasses['MWScrape'] = "$dir/MWScrape.php";

$wgAutoloadClasses['TPCPatchMap'] = "$dir/TPCPatchMap.php";
$wgAutoloadClasses['TPCParse'] = "$dir/TPCParse.php";
$wgAutoloadClasses['TPCServer'] = "$dir/TPCServer.php";
$wgAutoloadClasses['TPCServerLocal'] = "$dir/TPCServerLocal.php";
$wgAutoloadClasses['TPCState'] = "$dir/TPCState.php";
$wgAutoloadClasses['TPCStorage'] = "$dir/TPCStorage.php";
require_once("$dir/TPCTLPatches.php"); // contains a TPC hook
$wgAutoloadClasses['TPCUtil'] = "$dir/TPCUtil.php";

$wgAutoloadClasses['ApiEvalTitle'] = "$dir/ApiEvalTitle.php";
$wgAPIModules['evaltitle'] = 'ApiEvalTitle';

$wgExtensionMessagesFiles['TouhouPatchCenter'] = "$dir/TouhouPatchCenter.i18n.php";
// --------

// Rights
// ------
$wgAvailableRights[] = 'tpc-restricted';
$wgGroupPermissions['sysop']['tpc-restricted'] = true;
$wgGroupPermissions['patchdev']['tpc-restricted'] = true;
// ------

// TPC Hooks
// ---------
$hookDir = "$dir/hooks";
require_once("$hookDir/TPCBinhack.php");
require_once("$hookDir/TPCBreakpoint.php");
require_once("$hookDir/TPCInclude.php");
require_once("$hookDir/TPCInfo.php");
require_once("$hookDir/TPCParseContext.php");
require_once("$hookDir/TPCVersions.php");

require_once("$hookDir/TPCFmtCSV.php");
require_once("$hookDir/TPCFmtGentext.php");
require_once("$hookDir/TPCFmtMissions.php");
require_once("$hookDir/TPCFmtMsg.php");
require_once("$hookDir/TPCFmtMusic.php");
require_once("$hookDir/TPCFmtSpells.php");
require_once("$hookDir/TPCFmtStrings.php");
require_once("$hookDir/TPCFmtTheme.php");

require_once("$hookDir/TPCFmtTasofro.php");
// ---------

// Templates
// --------
$templateDir = "$dir/templates";
require_once("$templateDir/TPCTemplate.php");
require_once("$templateDir/thcrap_restricted_templates.php");
require_once("$templateDir/thcrap_servers.php");
require_once("$templateDir/thcrap_neighbors.php");
// --------

// MediaWiki hooks
// ---------------
$wgHooks['FileUpload'][] = 'TouhouPatchCenter::onFileUpload';
$wgHooks['FileDeleteComplete'][] = 'TouhouPatchCenter::onFileDeleteComplete';
$wgHooks['MultiContentSave'][] = 'TouhouPatchCenter::onMultiContentSave';
$wgHooks['PageSaveComplete'][] = 'TouhouPatchCenter::onPageSaveComplete';
$wgHooks['TitleMoveComplete'][] = 'TouhouPatchCenter::onTitleMoveComplete';
$wgHooks['CanonicalNamespaces'][] = 'TouhouPatchCenter::onCanonicalNamespaces';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'TouhouPatchCenter::onDatabaseUpdate';
// ---------------

// Patch namespace
$wgTPCPatchNamespace = 238;
