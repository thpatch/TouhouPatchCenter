 <?php

/**
  * Parser for breakpoints.
  * Registers the following template hooks:
  *
  * {{thcrap_breakpoint}}
  *
  * @file
  * @author Nmlgc
  */

class TPCBreakpoint {

	public static function onBreakpoint( $tpcState, $title, $temp ) {
		$type = TPCUtil::dictGet( $temp->params['type'] );
		if ( !$type ) {
			// no type, invalid breakpoint
			return true;
		}
		// Switch back to the top file of the current game
		$tpcState->switchDataFile( null );

		foreach ( $temp->params as $key => $val ) {
			if ( $key === "type" ) {
				// nope, have that one already
				continue;
			}
			$versions = TPCParse::parseVer( $val );
			foreach ( $versions as $ver => $val ) {
				$verFile = &$tpcState->getVersion( $ver );
				$val = trim( $val );
				if ( $val == "false" ) {
					$val = false;
				} else if ( $val == "true" ) {
					$val = true;
				}
				$verFile['breakpoints'][$type][$key] = $val;
			}
		}
		return true;
	}
}

$wgTPCHooks['thcrap_breakpoint'][] = 'TPCBreakpoint::onBreakpoint';
