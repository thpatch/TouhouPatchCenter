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
$wgAutoloadClasses['TPCUtil'] = "$dir/TPCUtil.php";

$wgAutoloadClasses['ApiEvalTitle'] = "$dir/ApiEvalTitle.php";
$wgAPIModules['evaltitle'] = 'ApiEvalTitle';

$wgExtensionMessagesFiles['TouhouPatchCenter'][] = "$dir/TouhouPatchCenter.i18n.php";
$wgExtensionMessagesFiles['TouhouPatchCenter'][] = "$dir/TouhouPatchCenter.i18n.magic.php";
// --------

// Rights
// ------
$wgAvailableRights[] = 'tpc-restricted';
$wgGroupPermissions['sysop']['tpc-restricted'] = true;
$wgGroupPermissions['patchdev']['tpc-restricted'] = true;
// ------

// Templates
// --------
$templateDir = "$dir/templates";
$wgAutoloadClasses['TPCTemplate'] = "$templateDir/TPCTemplate.php";
$wgAutoloadClasses['thcrap_restricted_templates'] = "$templateDir/thcrap_restricted_templates.php";
$wgAutoloadClasses['thcrap_servers'] = "$templateDir/thcrap_servers.php";
$wgAutoloadClasses['thcrap_neighbors'] = "$templateDir/thcrap_neighbors.php";
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

$wgHooks['ParserGetVariableValueSwitch'][] = 'TPCTemplate::runSubclass';
$wgHooks['GetMagicVariableIDs'][] = 'thcrap_neighbors::setup';
$wgHooks['GetMagicVariableIDs'][] = 'thcrap_restricted_templates::setup';
$wgHooks['GetMagicVariableIDs'][] = 'thcrap_servers::setup';
// ---------------

// Patch namespace
$wgTPCPatchNamespace = 238;

TouhouPatchCenter::setup();
