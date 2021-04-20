<?php

/**
  * Legacy entry point for the TouhouPatchCenter extension.
  *
  * @file
  * @author Nmlgc
  */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'TouhouPatchCenter' );
	wfWarn(
		'Deprecated PHP entry point used for the TouhouPatchCenter extension. ' .
		'Please use wfLoadExtension() instead, ' .
		'see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the TouhouPatchCenter extension requires MediaWiki 1.35+' );
}
