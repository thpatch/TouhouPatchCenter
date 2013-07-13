<?php

/**
  * Parser for Team Shanghai Alice spell cards.
  * Registers the following template hooks:
  *
  * {{thcrap_spell}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtSpells {

	public static function onSpell( &$tpcState, &$title, &$temp ) {
		$id = TPCUtil::dictGet( $temp->params['id'] );
		if ( !$id ) {
			return true;
		}
		$name = TPCUtil::dictGet( $temp->params['name'] );
		$owner = TPCUtil::dictGet( $temp->params['owner'] );
		// In-game ID starts from 0
		$id = intval( $id ) - 1;
		
		$spells = &$tpcState->switchDataFile( "spells.js" );
		if ( $name ) {
			$spells[$id] = $name;
		}

		// Comments...
		foreach ( $temp->params as $key => $val ) {
			if ( !strncasecmp( $key, "comment_", 8 ) and $val ) {
				$spellcomments = &$tpcState->switchDataFile( "spellcomments.js" );
				$cmt = &$spellcomments[$id];
				$lines = TPCUtil::scrapeLines( $val );
				$cmt[$key] = $lines;
				// Resolve owner in the correct language
				if ( $owner and !isset( $cmt['owner'] ) ) {
					$lang = $title->getPageLanguage();
					$cmt['owner'] = wfMessage( $owner )->inLanguage( $lang )->plain();
				}
			}
		}
		return true;
	}
}
  
$wgTPCHooks['thcrap_spell'][] = 'TPCFmtSpells::onSpell';
// "Historic templates"
$wgTPCHooks['spell_card'][] = 'TPCFmtSpells::onSpell';
