 <?php

/**
  * Parser for binary hacks.
  * Registers the following template hooks:
  *
  * {{thcrap_binhack}}
  *
  * @file
  * @author Nmlgc
  */

class TPCBinhack {

	public static function onBinhack( &$tpcState, $title, $temp ) {
		$id = TPCUtil::dictGet( $temp->params['id'] );
		if ( !$id ) {
			// Nope, we're not even trying to create GUIDs
			return true;
		}
		// Switch back to the top file of the current game
		$tpcState->switchDataFile( null );
		$baseFile = &$tpcState->getVersion( null );
		
		$title = TPCUtil::dictGet( $temp->params['title'] );
		$addr = TPCUtil::dictGet( $temp->params['addr'] );
		$code = TPCUtil::dictGet( $temp->params['code'] );
		// Only makes sense once we have a GUI
		// $desc = TPCUtil::dictGet( $temp->params['desc'] );
		// $dasm = TPCUtil::dictGet( $temp->params['dasm'] );

		// TODO: Refactor into something callback-based?
		$addr = TPCParse::parseVer( $addr );
		foreach ( $addr as $ver => $val ) {
			$verFile = &$tpcState->getVersion( $ver );
			preg_match_all( '/0x[0-9a-f]+/i', $val, $valArray);
			if ( !empty( $valArray[0] ) ) {
				$verFile['binhacks'][$id]['addr'] = $valArray[0];
			}
		}

		$code = TPCParse::parseVer( $code );
		foreach ( $code as $ver => $val ) {
			$verFile = &$tpcState->getVersion( $ver );
			$val = preg_replace('/\s+/', '', $val);
			if ( !empty( $val ) ) {
				$verFile['binhacks'][$id]['code'] = $val;
			}
		}

		$cont = &$baseFile['binhacks'][$id];
		$cont['title'] = $title;
		return true;
	}
}

$wgTPCHooks['thcrap_binhack'][] = 'TPCBinhack::onBinhack';
