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
	  * By assigning different names to $patches, $game and $file,
	  * one can access all directory levels on the server:
	  *
	  * +----------+-------+-------+----------------------+
	  * | $patches | $game | $file | Result               |
	  * +----------+-------+-------+----------------------+
	  * |          |       | set   | /filename            |
	  * | set      |       | set   | /patch/filename      |
	  * | set      | set   |       | /patch/game.ver.js   |
	  * | set      | set   | set   | /patch/game/filename |
	  * +----------+-------+-------+----------------------+
	  * 
	  * Currently, $version is only applied with the main file of a game,
	  * i.e. if $file is not set.
	  */

	// Gets directly written to JSON.
	// Format: [patch-relative destination FN] => array( contents... )
	public $jsonCache = array();

	// List of files to copy.
	// Format: [patch-relative destination FN] => [absolute source FN]
	public $copyCache = array();

	protected $curGame = null;
	protected $curVersion = null;
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
	  * Removes potentially dangerous characters from a file name
	  *
	  * @param string $fn File name
	  * @return string Sanitized file name
	  */
	public static function sanitizeFileName( $fn ) {
		// This _will_ need to be changed once we patch Tasofro games, since they
		// tend to use Japanese characters in their file names...
		return preg_replace( '/[^a-z0-9\._\- ]/i', '', $fn );
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
	  * Combines any permutation of $game, $version and $file
	  * to a complete, patch-relative file name.
	 */
	public function getFileName( $game, $version, $file ) {
		// Wave the magic wand
		$this->curGame = self::sanitizeFileName( $game );
		$this->curVersion = self::sanitizeFileName( $version );
		$this->curFile = self::sanitizeFileName( $file );

		if ( $this->curGame ) {
			if ( $this->curFile ) {
				return $this->curGame . '/' . $this->curFile;
			} else if ( $this->curVersion) {
				return $this->curGame . '.' . $this->curVersion . '.js';
			} else {
				return $this->curGame . '.js';
			}
		} else /* if ( $this->curFile ) */ {
			return $this->curFile;
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
	  * Get a different version of the current file.
	  */ 
	public function &getVersion( $version = null ) {
		$newFN = $this->getFileName( $this->curGame, $version, $this->curFile );
		return $this->jsonCache[ $newFN ];
	}

	public function switchGame( $game ) {
		$this->jsonContents = &$this->getFile( $game );
		return true;
	}

	public function switchTopFile( $file ) {
		$this->jsonContents = &$this->getFile( null, $file );
		return true;
	}

	public function switchDataFile( $file ) {
		$this->jsonContents = &$this->getFile( $this->curGame, $file );
		return true;
	}

	public function switchDataFilePatch( $file ) {
		return $this->switchDataFile( $file . '.jdiff' );
	}

	public function addCopy( $target, $source ) {
		$fn = $this->getFileName( $this->curGame, null, $target );
		$this->copyCache[ $fn ] = $source;
	}

	/**
	  * Returns an array of all files in this state object,
	  * together with the current time stamp.
	  */
	public function listFiles() {
		$files = array();
		$time = time();
		foreach ( $this->jsonCache as $fn => $array ) {
			if ( !$array ) {
				continue;
			}
			$files[$fn] = time();
		}
		foreach ( $this->copyCache as $target => $source ) {
			$files[$target] = filemtime( $source );
		}
		return $files;
	}
}
