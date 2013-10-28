<?php

/**
  * Patch state class.
  * Collects all patch data contained on one wiki page.
  *
  * @file
  * @author Nmlgc
  */

class TPCState
{
	/** 
	  * By assigning different names to $patches, $game, $file and $build,
	  * one can access all directory levels on the server:
	  *
	  * +----------+-------+-------+--------+--------------------------------+
	  * | $patches | $game | $file | $build | Result                         |
	  * +----------+-------+-------+--------+--------------------------------+
	  * |          |       | set   |        | /filename.ext                  |
	  * | set      |       | set   |        | /patch/filename.ext            |
	  * | set      |       | set   | set    | /patch/filename.build.ext      |
	  * | set      | set   |       |        | /patch/game.js                 |
	  * | set      | set   |       | set    | /patch/game.build.js           |
	  * | set      | set   | set   |        | /patch/game/filename.ext       |
	  * | set      | set   | set   | set    | /patch/game/filename.build.ext |
	  * +----------+-------+-------+--------+--------------------------------+
	  *
	  * "ext" contains every extension of a file name, beginning from the first dot.
	  */

	// Gets directly written to JSON.
	// Format: [patch-relative destination FN] => array( contents... )
	public $jsonCache = array();

	// List of files to copy.
	// Format: [patch-relative destination FN] => [absolute source FN]
	public $copyCache = array();

	protected $curGame = null;
	protected $curBuild = null;
	protected $curFile = null;

	// Array of patches the current state will be stored in
	public $patches = null;

	// Array of current JSON file.
	public $jsonContents;

	public function getCurGame() {
		return $this->curGame;
	}
	public function getCurFile() {
		return $this->curFile;
	}		

	/**
	  * Removes potentially dangerous stuff from a file name.
	  *
	  * @param string $fn File name
	  * @return string Sanitized file name
	  */
	public static function sanitizeFileName( $fn ) {
		// This _will_ need to be changed once we patch Tasofro games, since they
		// tend to use Japanese characters in their file names...
		$ret = preg_replace( '/[^a-z0-9\._\- \/]/i', '', $fn );

		// Directory traversal
		$ret = preg_replace( '/^\/|\.\.\//i', '', $fn );

		return $ret;
	}

	public function init( $title ) {
		// Is this page already mapped to a patch?
		if ( !TPCPatchMap::isPatchRootPage( $title ) ) {
			$map = TPCPatchMap::get( $title );
			if ( !$map ) {
				// Nope, nothing we care about
				return false;
			}
			$this->patches = $map->pm_patch;
			$this->switchGame( $map->pm_game );
		} else {
			// Root page of a patch. Set the name
			$this->patches = array( strtolower( $title->getDBKey() ) );
		}
		return true;
	}

	/**
	  * Combines any permutation of $game, $build and $file
	  * to a complete, patch-relative file name.
	 */
	public function getFileName( $game, $build, $file ) {
		// Wave the magic wand
		$this->curGame = self::sanitizeFileName( $game );
		$this->curBuild = self::sanitizeFileName( $build );
		$this->curFile = self::sanitizeFileName( $file );

		if ( !$this->curFile ) {
			$fn = $this->curGame . '.js';
		} else {
			$fn = $this->curFile;
		}

		if ( $this->curBuild ) {
			$dotPos = strpos( $fn, '.' );
			if ( $dotPos === false ) {
				$dotPos = strlen( $fn );
			}
			$fn = substr( $fn, 0, $dotPos ) . ".$build" . substr( $fn, $dotPos );
		}

		if ( $this->curGame and $this->curFile ) {
			return $this->curGame . '/' . $fn;
		} else {
			return $fn;
		}
	}

	public function &getFile( $game = null, $file = null ) {
		// Just that we have something to reference if $newFN turns out to be null
		$temp = null;

		$newFN = $this->getFileName( $game, null, $file );
		// No, the ternary operator is not a functionally equivalent shortcut here
		if ( $newFN ) {
			return $this->jsonCache[$newFN];
		} else {
			return $temp;
		}
	}

	/**
	  * Get a different build of the current file.
	  */
	public function &getBuild( $build = null ) {
		$newFN = $this->getFileName( $this->curGame, $build, $this->curFile );
		return $this->jsonCache[ $newFN ];
	}

	public function &switchGame( $game ) {
		$this->jsonContents = &$this->getFile( $game );
		return $this->jsonContents;
	}

	public function &switchFile( $file ) {
		$this->jsonContents = &$this->getFile( null, $file );
		return $this->jsonContents;
	}

	public function &switchGameFile( $file ) {
		$this->jsonContents = &$this->getFile( $this->curGame, $file );
		return $this->jsonContents;
	}

	public function &switchGameFilePatch( $file ) {
		return $this->switchGameFile( $file . '.jdiff' );
	}

	public function addCopy( $target, $source ) {
		$fn = $this->getFileName( $this->curGame, null, $target );
		$this->copyCache[ $fn ] = $source;
	}

	/**
	  * Returns an array of all files in this state object.
	  */
	public function listFiles() {
		return array_merge(
			array_keys( $this->jsonCache ),
			array_keys( $this->copyCache )
		);
	}
}
