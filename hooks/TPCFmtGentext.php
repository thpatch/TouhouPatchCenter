<?php

/**
  * Parser for generic plaintext translations.
  * Registers the following template hooks:
  *
  * {{thcrap_gentext}} / {{gentext}}
  * {{trophy}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtGentext {

	public static function addGentext( &$tpcState, $id, $tl ) {
		if ( $tl !== "" ) {
			if ( is_array( $tl ) && count( $tl ) == 1 ) {
				$tl = $tl[0];
			}
			$tpcState->jsonContents[$id] = $tl;
		}
		return true;
	}

	public static function onGentext( &$tpcState, &$title, &$temp ) {
		$id = TPCUtil::dictGet( $temp->params['id'] );
		$tl = TPCUtil::dictGet( $temp->params['tl'] );
		return self::addGentext( $tpcState, $id, TPCParse::parseLines( $tl ));
	}

	public static function onTrophy( &$tpcState, &$title, &$temp ) {
		$id = TPCUtil::dictGet( $temp->params['id'] );
		$title = TPCUtil::dictGet( $temp->params['title'] );
		$locked = TPCUtil::dictGet( $temp->params['locked'] );
		$unlocked = TPCUtil::dictGet( $temp->params['unlocked'] );
		self::addGentext( $tpcState, $id, TPCUtil::sanitize( $title ));
		self::addGentext( $tpcState, $id . "_0", TPCParse::parseLines( $locked ));
		self::addGentext( $tpcState, $id . "_1", TPCParse::parseLines( $unlocked ));
		return true;
	}

	public static function onAbility( &$tpcState, &$title, &$temp ) {
		$id = TPCUtil::dictGet( $temp->params['id'] );
		$title = TPCUtil::dictGet( $temp->params['title'] );
		$description = TPCUtil::dictGet( $temp->params['description'] );
		self::addGentext( $tpcState, $id, TPCUtil::sanitize( $title ));
		self::addGentext( $tpcState, $id . "_0", TPCParse::parseLines( $description ));
		return true;
	}
}
$wgTPCHooks['thcrap_gentext'][] = 'TPCFmtGentext::onGentext';
$wgTPCHooks['gentext'][] = 'TPCFmtGentext::onGentext';
$wgTPCHooks['trophy'][] = 'TPCFmtGentext::onTrophy';
$wgTPCHooks['ability'][] = 'TPCFmtGentext::onAbility';
