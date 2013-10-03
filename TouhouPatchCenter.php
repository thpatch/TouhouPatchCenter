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

// Other libraries
// ---------------

// phpseclib (required for SFTP storage back-end)
require_once('Net/SFTP.php');
require_once('Crypt/RSA.php');
// ---------------

// Includes
// --------
$dir = __DIR__;
$wgAutoloadClasses['TouhouPatchCenter'] = "$dir/TouhouPatchCenter.body.php";
$wgAutoloadClasses['MWScrape'] = "$dir/MWScrape.php";

$wgAutoloadClasses['TPCPatchMap'] = "$dir/TPCPatchMap.php";
$wgAutoloadClasses['TPCParse'] = "$dir/TPCParse.php";
$wgAutoloadClasses['TPCServer'] = "$dir/TPCServer.php";
$wgAutoloadClasses['TPCServerLocal'] = "$dir/TPCServerLocal.php";
if ( class_exists( 'Net_SFTP', false ) ) {
	$wgAutoloadClasses['TPCServerSFTP'] = "$dir/TPCServerSFTP.php";
}
$wgAutoloadClasses['TPCState'] = "$dir/TPCState.php";
$wgAutoloadClasses['TPCStorage'] = "$dir/TPCStorage.php";
require_once("$dir/TPCTLPatches.php"); // contains a TPC hook
$wgAutoloadClasses['TPCUtil'] = "$dir/TPCUtil.php";

$wgExtensionMessagesFiles['TouhouPatchCenter'] = "$dir/TouhouPatchCenter.i18n.php";
// --------

// TPC Hooks
// ---------
$hookDir = "$dir/hooks";
require_once("$hookDir/TPCBinhack.php");
require_once("$hookDir/TPCBreakpoint.php");
require_once("$hookDir/TPCInclude.php");
require_once("$hookDir/TPCInfo.php");
require_once("$hookDir/TPCParseContext.php");
require_once("$hookDir/TPCVersions.php");

require_once("$hookDir/TPCFmtMsg.php");
require_once("$hookDir/TPCFmtMusic.php");
require_once("$hookDir/TPCFmtSpells.php");
require_once("$hookDir/TPCFmtStrings.php");
// ---------

// Templates
// --------
$templateDir = "$dir/templates";
require_once("$templateDir/thcrap_servers.php");
// --------

// MediaWiki hooks
// ---------------
$wgHooks['FileUpload'][] = 'TouhouPatchCenter::onFileUpload';
$wgHooks['PageContentSaveComplete'][] = 'TouhouPatchCenter::onPageContentSaveComplete';
$wgHooks['TitleMoveComplete'][] = 'TouhouPatchCenter::onTitleMoveComplete';
$wgHooks['CanonicalNamespaces'][] = 'TouhouPatchCenter::onCanonicalNamespaces';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'TouhouPatchCenter::onDatabaseUpdate';
// ---------------

// Patch namespace
$wgTPCPatchNamespace = 238;

// Other constants
if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
	define( 'TPC_JSON_OPTS', 
		JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
} else {
	define( 'TPC_JSON_OPTS', 0 );
}
