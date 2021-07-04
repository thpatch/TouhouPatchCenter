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
		$lang = $title->getSubpageText();

		while( $title ) {
			$content = WikiPage::factory( $title )->getContent();
			if ( !is_a( $content, 'TextContent' ) ) {
				return;
			}
			$title = $content->getRedirectTarget();

			// Don't cross language boundaries. That's what
			// client-side patch stacking is there for.
			if ( $title && ( $title->getSubpageText() != $lang ) ) {
				return;
			}
		};

		$text = $content->getText();
		if ( $text ) {
			$themes[$id] = TPCUtil::sanitize( $text );
		}
	}
}
