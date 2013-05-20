<?php

/**
  * Parser for version information.
  * Registers the following template hooks:
  *
  * {{thcrap_ver_info}}
  * {{thcrap_ver_info/Header}}
  *
  * @file
  * @author Nmlgc
  */

class TPCVersions {

	public static function onVerInfoHeader( &$tpcState, $title, $temp ) {
		$tpcState->switchTopFile( "versions.js" );
		return true;
	}

	public static function onVerInfo( &$tpcState, $title, $temp ) {
		$ver = array(
			TPCUtil::dictGet( $temp->params[1] ),
			TPCUtil::dictGet( $temp->params[2] ),
			TPCUtil::dictGet( $temp->params[3], '(original)' )
		);

		if ( isset($temp->params['hash']) ) {
	 		$hashes = &$tpcState->jsonContents['hashes'];	
			$hashes[ $temp->params['hash'] ] = $ver;
		}
		if ( isset($temp->params['size']) ) {
			$sizes = &$tpcState->jsonContents['sizes'];
			$sizes[ $temp->params['size'] ] = $ver;
		}
		return true;
	}
}

$wgTPCHooks['thcrap_version_info/header'][] = 'TPCVersions::onVerInfoHeader';
$wgTPCHooks['thcrap_ver_info/header'][] = 'TPCVersions::onVerInfoHeader';
$wgTPCHooks['thcrap_version_info'][] = 'TPCVersions::onVerInfo';
$wgTPCHooks['thcrap_ver_info'][] = 'TPCVersions::onVerInfo';
