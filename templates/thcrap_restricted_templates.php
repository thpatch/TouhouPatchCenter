<?php

/**
  * Prints a list of restricted templates from $wgTPCRestrictedTemplates.
  *
  * @file
  * @author Nmlgc
  */

class thcrap_restricted_templates extends TPCTemplate {

	public static function run( &$parser, &$cache, &$magicWordId, &$ret, &$frame ) {
		global $wgTPCRestrictedTemplates;

		$ret = "<ul>";
		foreach ( $wgTPCRestrictedTemplates as $temp ) {
			$ret .= "<li>{{int:tpc-template|$temp}}</li>";
		}
		$ret .= '</ul>';
		$ret = $parser->recursiveTagParse( $ret, $frame );
		return true;
	}
}
