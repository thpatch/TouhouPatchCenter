<?php

/**
  * Parser for Team Shanghai Alice .msg dialogs.
  * Registers the following template hooks:
  *
  * {{thcrap_msg}} / {{dialogtable}} / {{dt}}
  * {{thcrap_msg_assist}} / {{msgassist}}
  * {{thcrap_msg/footer}} / {{dialogtable/footer}} / {{dt/footer}}
  * {{thcrap_msg_parse}} / {{msgparse}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtMsg {

	const TAB = '<l$>';

	static protected $charTypes = [
		'assist' => [
			'prefix' => '<t$%s >(',
			'postfix' => ')'
		],
		'tabchar' => [
			'prefix' => '<r$%s>',
			'postfix' => ''
		]
	];

	const TABREF_FORMAT = '<ts%s>';

	const REGEX_CODE = '/#(?P<entry>[\d]+)@(?P<time>[\d]+)/';

	public static function formatSlot( &$time, &$type, &$index ) {
		// Much faster than sprintf, by the way
		if ( $type ) {
			return $time . '_' . $type . '_' . $index;
		} else {
			return $time . '_' . $index;
		}
	}

	protected static function renderRuby( &$lines ) {
		$FORMAT_RUBY = "|\t%s\t,\t%s\t,%s";
		for( $i = 0; $i < count( $lines ); $i++ ) {
			$line = &$lines[$i];
			if ( !TPCParse::parseRuby( $m, $line ) ) {
				continue;
			}
			$offset = substr( $line, 0, $m[0][1] );
			$base = $m[2][0];
			$rest = substr( $line, $m[0][1] + strlen( $m[0][0] ) );
			$lines[$i] = ( $offset . $base . $rest );

			$rubyLine = sprintf( $FORMAT_RUBY, $offset, $base, $m[3][0] );
			array_splice( $lines, $i, 0, $rubyLine );
			$i++;
		}
	}

	public static function onMsg( &$tpcState, &$title, &$temp ) {
		$code = ( $temp->params['code'] ?? null );
		if ( ( $code === null ) || !preg_match( self::REGEX_CODE, $code, $m ) ) {
			return true;
		}
		$lang = $title->getPageLanguage();

		$entry = $m['entry'];
		$time = $m['time'];

		$lines = TPCParse::parseLines( $temp->params['tl'] );
		$type = ( $temp->params[1] ?? null );

		// h1 index hack... meh.
		$indexType = ( ( $type === "h1" ) ? "h1" : null );

		// Time index
		$timeIndex = &$tpcState->msgTimeIndex[$indexType];
		if(
			( $entry === ( $tpcState->msgLastEntry[$indexType] ?? null ) ) and
			( $time === ( $tpcState->msgLastTime[$indexType] ?? null ) ) and
			( $tpcState->msgLastType === $indexType )
		) {
			$timeIndex++;
		} else {
			$timeIndex = 0;
		}

		// Line processing
		if ( $lines ) {
			// Special types
			if ( isset( self::$charTypes[$type] ) ) {
				$typeSpec = &self::$charTypes[$type];
				// Start line for indentation
				$i = 0;
				$prefix = '';

				// Prefix first line
				if ( $type === 'assist' and isset( $tpcState->msgAssistName ) ) {
					$prefix = sprintf( $typeSpec['prefix'], $tpcState->msgAssistName );
					$i = 1;
				} else if ( $type === 'tabchar' and isset( $temp->params['char'] ) ) {
					$char = wfMessage( $temp->params['char'] )->inLanguage( $lang )->plain();
					// Add two spaces for... spacing
					$char .= '  ';
					$prefix = sprintf( $typeSpec['prefix'], $char );

					// Write tab reference string
					if ( !isset ( $tpcState->tabref ) ) {
						$tpcState->tabref = array();
					}
					if ( !in_array( $char, $tpcState->tabref ) ) {
						$tpcState->tabref[] = $char;
					}
					$i = 1;
				}
				$lines[0] = $prefix . $lines[0];

				// Indent all following lines
				for ( $i; $i < count( $lines ); $i++ ) {
					$lines[$i] = self::TAB . ' ' . $lines[$i];
				}

				// Postfix last line
				$lines[count( $lines ) - 1] .= $typeSpec['postfix'];
			}

			// Yeah, maybe we should only do this based on some previous condition,
			// but profiling tells that it hardly matters anyway...
			self::renderRuby( $lines );

			$slot = self::formatSlot( $time, $indexType, $timeIndex );
			$cont = &$tpcState->jsonContents[$entry][$slot];
			$cont['lines'] = &$lines;

			// Copy-paste the dialogue for th18 Stage 6, so that translators don't have to.
			$altentry = ( $temp->params['altentry'] ?? null );
			if ( $altentry !== null ) {
				$tpcState->jsonContents[$altentry][$slot]['lines'] = &$lines;
			}

			// Remember the first one for tabref
			if ( !isset( $tpcState->msgFirstLine ) ) {
				$tpcState->msgFirstLine = &$lines[0];
			}

			// Set type... or don't, the patcher doesn't care.
			// Don't know why the prototype versions had that in the first place...
			/*if( $type ) {
				$cont['type'] = $type;
			}*/
		}

		$tpcState->msgLastEntry[$indexType] = $entry;
		$tpcState->msgLastTime[$indexType] = $time;
		$tpcState->msgLastType = $indexType;
		return true;
	}

	public static function onMsgAssist( &$tpcState, &$title, &$temp ) {
		$tpcState->msgAssistName = TPCUtil::sanitize( $temp->params[1] );
		return true;
	}

	public static function onMsgFooter( &$tpcState, &$title, &$temp ) {
		if ( isset( $tpcState->msgFirstLine ) and isset( $tpcState->tabref ) ) {
			$tabrefStr = null;
			foreach ( $tpcState->tabref as $char ) {
				$tabrefStr .= '$' . $char;
			}
			$refStr = sprintf( self::TABREF_FORMAT, $tabrefStr );
			$tpcState->msgFirstLine = $refStr . $tpcState->msgFirstLine;
			unset( $tpcState->msgFirstLine );
			unset( $tpcState->tabref );
		}
		return true;
	}

	public static function onMsgParse( &$tpcState, &$title, &$temp ) {
		$tpcState->switchGameFilePatch( $temp->params['file'] ?? null );
		return true;
	}
}

TouhouPatchCenter::registerHook( 'thcrap_msg', 'TPCFmtMsg::onMsg' );
TouhouPatchCenter::registerHook( 'thcrap_msg_assist', 'TPCFmtMsg::onMsgAssist' );
TouhouPatchCenter::registerHook( 'thcrap_msg/footer', 'TPCFmtMsg::onMsgFooter' );
TouhouPatchCenter::registerHook( 'thcrap_msg_parse', 'TPCFmtMsg::onMsgParse' );
// Short versions
TouhouPatchCenter::registerHook( 'dialogtable', 'TPCFmtMsg::onMsg' );
TouhouPatchCenter::registerHook( 'dt', 'TPCFmtMsg::onMsg' );
TouhouPatchCenter::registerHook( 'msgassist', 'TPCFmtMsg::onMsgAssist' );
TouhouPatchCenter::registerHook( 'dialogtable/footer', 'TPCFmtMsg::onMsgFooter' );
TouhouPatchCenter::registerHook( 'dt/footer', 'TPCFmtMsg::onMsgFooter' );
TouhouPatchCenter::registerHook( 'msgparse', 'TPCFmtMsg::onMsgParse' );
