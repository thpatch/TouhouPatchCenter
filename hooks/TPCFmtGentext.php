<?php

/**
  * Parser for generic plaintext translations.
  * Registers the following template hooks:
  *
  * {{thcrap_gentext}} / {{gentext}}
  * {{thcrap_trophy}} / {{trophy}}
  * {{thcrap_ability}} / {{ability}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtGentext {

	public static function addGentext( &$tpcState, $id, $tl ) {
		if ( $tl !== "" && $tl !== null ) {
			if ( is_array( $tl ) && count( $tl ) == 1 ) {
				$tl = $tl[0];
			}
			$tpcState->jsonContents[$id] = $tl;
		}
		return true;
	}

	public static function onGentext( &$tpcState, &$title, &$temp ) {
		$id = $temp->params['id'];
		$tl = ( $temp->params['tl'] ?? null );
		return self::addGentext( $tpcState, $id, TPCParse::parseLines( $tl ));
	}

	public static function onTrophy( &$tpcState, &$title, &$temp ) {
		$id = $temp->params['id'];
		$title = ( $temp->params['title'] ?? null );
		$locked = ( $temp->params['locked'] ?? null );
		$unlocked = ( $temp->params['unlocked'] ?? null );
		self::addGentext( $tpcState, $id, TPCUtil::sanitize( $title ));
		self::addGentext( $tpcState, $id . "_0", TPCParse::parseLines( $locked ));
		self::addGentext( $tpcState, $id . "_1", TPCParse::parseLines( $unlocked ));
		return true;
	}

	public static function onAbility( &$tpcState, &$title, &$temp ) {
		$id = $temp->params['id'];
		$title = ( $temp->params['title'] ?? null );
		$description = ( $temp->params['description'] ?? null );
		self::addGentext( $tpcState, $id, TPCUtil::sanitize( $title ));
		self::addGentext( $tpcState, $id . "_0", TPCParse::parseLines( $description ));
		return true;
	}
}

TouhouPatchCenter::registerHook( 'thcrap_gentext', 'TPCFmtGentext::onGentext' );
TouhouPatchCenter::registerHook( 'thcrap_trophy', 'TPCFmtGentext::onTrophy' );
TouhouPatchCenter::registerHook( 'thcrap_ability', 'TPCFmtGentext::onAbility' );
// Short versions
TouhouPatchCenter::registerHook( 'gentext', 'TPCFmtGentext::onGentext' );
TouhouPatchCenter::registerHook( 'trophy', 'TPCFmtGentext::onTrophy' );
TouhouPatchCenter::registerHook( 'ability', 'TPCFmtGentext::onAbility' );
