<?php

/**
  * Base class for templates rendered by the extension.
  *
  * @file
  * @author Nmlgc
  */

abstract class TPCTemplate {
	abstract public static function run( &$parser, &$frame ): string;

	public static function runSubclass( &$parser, &$cache, &$magicWordId, &$ret, &$frame ) {
		if ( is_subclass_of( $magicWordId, get_called_class() ) ) {
			$ret = $magicWordId::run( $parser, $frame );
			// Why do *we* have to write this line, as of MediaWiki 1.35?! WHY?!?
			$cache[$magicWordId] = $ret;
		}
		return true;
	}

	/**
	  * GetMagicVariableIDs hook.
	  */
	public static function setup( &$variableIDs ) {
		$variableIDs[] = get_called_class();
		return true;
	}
}
