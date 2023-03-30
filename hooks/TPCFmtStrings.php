<?php

/**
  * Parser for hardcoded strings.
  * Registers the following template hooks:
  *
  * {{thcrap_string_def}}
  * {{thcrap_string_loc}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtStrings {

	public static function onDef( &$tpcState, &$title, &$temp ) {
		$id = ( $temp->params['id'] ?? null );
		$tl = ( $temp->params['tl'] ?? null );
		if ( empty( $id ) or empty( $tl ) ) {
			return true;
		}
		$tl = TPCUtil::sanitize( $tl, false );
		if ( isset( $temp->params['ascii'] ) ) {
			// Try to transliterate the string, then limit it to the ASCII range
			// The exact transliteration results are up to the version of libiconv that PHP is
			// linked to, so let's at least define some clear rules for whitespace.
			$tl = str_replace( [ "\u{00a0}", "\u{3000}" ], ' ', $tl );

			// Hooray, locale dependence! Required for iconv() to do any transliteration at all,
			// instead of just swallowing non-ASCII characters.
			// (See the various comments at https://www.php.net/manual/en/function.iconv.php.)
			setlocale( LC_CTYPE, 'en_US.UTF-8' );

			$tl = iconv( "UTF-8", "ASCII//TRANSLIT//IGNORE", $tl );
			$tl = preg_replace( '/[^(\x20-\x7F)]/i', '', $tl );
		}
		$stringdefs = &$tpcState->switchFile( "stringdefs.js" );
		$stringdefs[$id] = $tl;
		return true;
	}
}
TouhouPatchCenter::registerHook( 'thcrap_string_def', 'TPCFmtStrings::onDef' );
// Short versions
TouhouPatchCenter::registerHook( 'stringdef', 'TPCFmtStrings::onDef' );
