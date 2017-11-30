<?php

/**
  * Parser for theme names.
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtTheme {

	// Fetches and stores a theme name in themes.js, resolving any redirects
	// in the process.
	public static function onTheme( &$tpcState, &$title, &$id ) {
		$themes = &$tpcState->getFile( null, "themes.js" );

		do {
			$page = WikiPage::factory( $title );
			$content = $page->getContent();
		} while ( $content and $title = $content->getRedirectTarget() );
		$text = $page->getText();
		if ( $text ) {
			$themes[$id] = TPCUtil::sanitize( $text );
		}
	}
}
