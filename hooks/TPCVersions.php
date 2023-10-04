<?php

/**
  * Parser for version information.
  * Registers the following template hooks:
  *
  * {{thcrap_version_info}} / {{thcrap_ver_info}}
  * {{thcrap_version_info/header}} / {{thcrap_ver_info/header}}
  *
  * @file
  * @author Nmlgc
  */

class TPCVersions {

	public static function onVerInfoHeader( &$tpcState, $title, $temp ) {
		$tpcState->switchFile( "versions.js" );
		return true;
	}

	public static function onVerInfo( &$tpcState, $title, $temp ) {
		$ver = array( $temp->params[1], $temp->params[2], ( $temp->params[3] ?? '(original)' ) );

		// Optional code page specification
		if ( isset( $temp->params[4] ) ) {
			$ver[] = intval( $temp->params[4] );
		}

		if ( isset($temp->params['hash']) and !empty( $temp->params['hash'] ) ) {
			$hashes = &$tpcState->jsonContents['hashes'];
			$hashes[ $temp->params['hash'] ] = $ver;
		}
		if ( isset($temp->params['size']) and !empty( $temp->params['size'] ) ) {
			$sizes = &$tpcState->jsonContents['sizes'];
			$sizes[ $temp->params['size'] ] = $ver;
		}
		return true;
	}
}

TouhouPatchCenter::registerRestrictedHook( 'thcrap_version_info', 'TPCVersions::onVerInfo' );
TouhouPatchCenter::registerHook( 'thcrap_version_info/header', 'TPCVersions::onVerInfoHeader' );
// Short versions
TouhouPatchCenter::registerRestrictedHook( 'thcrap_ver_info', 'TPCVersions::onVerInfo' );
TouhouPatchCenter::registerHook( 'thcrap_ver_info/header', 'TPCVersions::onVerInfoHeader' );
