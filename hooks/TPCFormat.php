 <?php

/**
  * Parser for miscellaneous format descriptors.
  * Registers the following template hooks:
  *
  * {{thcrap_format}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFormat {

	protected static function parseOpcode( &$cont, &$params ) {
		$opcode = &$cont[$params[1]];
		foreach ( $params as $key => $val )  {
			if ( $key == 1 ) {
				continue;
			}
			$opcode[$key] = $val;
		}
	}

	public static function onFormat( $tpcState, $title, $temp ) {
		$format = TPCUtil::dictGet( $temp->params['id'] );
		if ( !$format ) {
			// Nope, we're not even trying to create GUIDs
			return true;
		}
		$tpcState->switchTopFile( "formats.js" );
		$cont = &$tpcState->jsonContents[$format];

		foreach ( $temp->params as $key => $val ) {
			switch ( $key ) {
				case "id":
					// nope, have that one already
					break;
				case "encryption":
					$func = TPCParse::parseFunc( $val );
					$cont['encryption']['vars'] = reset($func);
					$cont['encryption']['func'] = key($func);
					break;
				case "opcodes":
					$opcodes = MWScrape::toArray( $val );
					foreach ( $opcodes as $op )  {
						if ( $op->name === "thcrap_format_opcode" and $op->params[1] ) {
							self::parseOpcode( $cont['opcodes'], $op->params );
						}
					}
					break;
				default:
					$cont[$key] = $val;
					break;
			}
		}
		return true;
	}
}

$wgTPCHooks['thcrap_format'][] = 'TPCFormat::onFormat';
