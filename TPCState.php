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

	// List of files to delete.
	// Format: [patch-relative destination FN, patch-relative destination FN, …]
	public $deletionCache = array();

	protected $curGame = null;
	protected $curBuild = null;
	protected $curFile = null;

	// Array of patches the current state will be stored in
	public $patches = null;

	// Array of current JSON file.
	public $jsonContents;

	public function __construct( array $patches, $game, $file ) {
		$this->patches = $patches;
		$this->curGame = $game;
		$this->curFile = $file;
	}

	public function getCurGame() {
		return $this->curGame;
	}
	public function getCurFile() {
		return $this->curFile;
	}

	/**
	  * Removes potentially dangerous stuff from a file name.
	  *
	  * @param string File name
	  * @return string Sanitized file name
	  */
	public static function sanitizeFileName( string $fn ): string {
		// This _will_ need to be changed once we patch Tasofro games, since they
		// tend to use Japanese characters in their file names...
		$ret = preg_replace( '/[^a-z0-9\._\- \/]/i', '', $fn );

		// Directory traversal
		$ret = preg_replace( '/^\/|\.\.\//i', '', $fn );

		return $ret;
	}

	/**
	  * Combines any permutation of $game, $build and $file
	  * to a complete, patch-relative file name.
	 */
	public function getFileName( $game, $build, $file ) {
		// Wave the magic wand
		$this->curGame = ( $game ? self::sanitizeFileName( $game ) : null );
		$this->curBuild = ( $build ? self::sanitizeFileName( $build ) : null );
		$this->curFile = ( $file ? self::sanitizeFileName( $file ) : null );

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

	public function addDeletion( $target ) {
		$fn = $this->getFileName( $this->curGame, null, $target );
		$this->deletionCache[] = $fn;
	}

	/**
	  * Returns an array of all files in this state object.
	  */
	public function listFiles() {
		return array_merge(
			array_keys( $this->jsonCache ),
			array_keys( $this->copyCache ),
			array_keys( $this->deletionCache )
		);
	}

	/**
	  * Returns a per-file translation unit counter, incremented for every call
	  * to autoCode(), that can be overriden by a 'code' parameter in [temp].
	  * Should be called regardless of the template's lines being set, obviously.
	  */
	public function autoCode( &$temp ) {
		$curFile = $this->getCurFile();
		$code = ( $this->autoCodes[ $curFile ] ?? 0 );
		$code = ( $temp->params['code'] ?? ++$code );
		$this->autoCodes[ $curFile ] = $code;
		return $code;
	}

	/**
	  * Creates a new patch state object, initialized with the patch and any game or target file
	  * mapped to the given title, or returns `null` if that title isn't patch-mapped.
	  *
	  * @param Title $title
	  * @return TPCState|null
	  */
	public static function from( Title &$title ) {
		$map = TPCPatchMap::get( $title );
		if ( !$map or !$map->pm_patch ) {
			// Nope, nothing we care about
			return null;
		}
		return new TPCState( $map->pm_patch, $map->pm_game, $map->pm_target );
	}
}
