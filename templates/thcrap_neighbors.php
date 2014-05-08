<?php

/**
  * Prints some information about the neighbors of the server repository into a wiki table.
  *
  * @file
  * @author Nmlgc
  */

class thcrap_neighbors extends TPCTemplate {

	public static function run( &$parser, &$cache, &$magicWordId, &$ret, &$frame ) {
		global $wgTPCRepoNeighbors;

		// Table header
		$ret = "{| class=\"wikitable\"\n|-\n! URL\n";
		$ret .= "\n! Title\n";
		$ret .= "\n! Patches\n";
		foreach ( $wgTPCRepoNeighbors as $i ) {
			$data = Http::get( $i . 'repo.js' );
			if ( $data ) {
				$repoJS = json_decode( $data, true );
			} else {
				$repoJS = NULL;
			}
			$ret .= "|-\n";
			$ret .= "| {$i}\n";
			if ( $repoJS ) {
				$ret .= "| {$repoJS['title']}\n";
				$ret .= "|\n";
				foreach ( $repoJS['patches'] as $id => $title ) {
					$ret .= "*<tt>$id</tt> ($title)\n";
				}
			} else {
				$ret .= '| ?\n';
				$ret .= '| ?\n';
			}
		}
		$ret .= "|}";
		return true;
	}
}

$wgHooks['MagicWordwgVariableIDs'][] = 'thcrap_neighbors::setup';
