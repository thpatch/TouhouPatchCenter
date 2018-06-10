<?php

/**
  * Parser for th095 and th125 mission descriptions.
  * Registers the following template hooks:
  *
  * {{thcrap_mission}}
  *
  * @file
  * @author Egor
  */

class TPCFmtMissions {

	const LINES = 3;

	public static function formatSlot( &$chara, &$stage, &$scene ) {
		return $chara . '_' . $stage . '_' . $scene;
	}

	protected static function renderRuby( &$lines, &$furi ) {
		$REGEX_RUBY = '/\{\{\s*ruby(-ja)*\s*\|\s*(.*?)\s*\|\s*(.*?)\s*\}\}/';
		foreach ( $lines as $key => &$i ) {
			if ( !preg_match( $REGEX_RUBY, $i, $m, PREG_OFFSET_CAPTURE) ) {
				continue;
			}
			$offset = substr( $i, 0, $m[0][1] );
			$base = $m[2][0];
			$rest = substr( $i, $m[0][1] + strlen( $m[0][0] ) );

			$i = $offset . $base . $rest;
			$furi[$key] = [$offset, $base];
			$lines[$key + self::LINES] = $m[3][0];
		}
	}

	protected static function fixBase1( &$var ) {
		if ( is_numeric( $var ) ) {
			$var = intval( $var ) - 1;
		}
	}

	protected static function fillGaps( &$arr, $val ) {
		end( $arr );
		$len = key( $arr );
		reset( $arr );
		for( $i = 0; $i < $len; $i++ ) {
			if( !isset( $arr[$i] ) ) {
				$arr[$i] = $val;
			}
		}

		// Needed to ensure that $arr is sequential
		ksort( $arr );
	}

	public static function onMission( &$tpcState, &$title, &$temp ) {
		$chara = TPCUtil::dictGet( $temp->params['chara'] );
		$stage = TPCUtil::dictGet( $temp->params['stage'] );
		$scene = TPCUtil::dictGet( $temp->params['scene'] );
		if( !$chara ) {
			$chara = 1;
		}
		if( !$stage || !$scene ) {
			return true;
		}
		self::fixBase1( $chara );
		self::fixBase1( $stage );
		self::fixBase1( $scene );

		$lines = TPCParse::parseLines( $temp->params['tl'] );

		// Line processing
		if ( $lines ) {
			array_splice( $lines, self::LINES ); // limit input to 3 lines
			$furi = [];
			self::renderRuby( $lines, $furi );

			self::fillGaps( $lines, " " );
			self::fillGaps( $furi, -1 );

			$slot = self::formatSlot( $chara, $stage, $scene );
			$missions = &$tpcState->switchGameFile( "missions.js" );
			$mission = &$missions[$slot];
			$mission['lines'] = &$lines;
			if( count( $furi ) > 0 ) {
				$mission['furi'] = &$furi;
			}
		}

		return true;
	}

}

$wgTPCHooks['thcrap_mission'][] = 'TPCFmtMissions::onMission';
// Short versions
$wgTPCHooks['mission'][] = 'TPCFmtMissions::onMission';
