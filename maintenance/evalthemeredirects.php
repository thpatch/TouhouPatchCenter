<?php

/**
  * Runs evalTitle on all themedb redirects
  *
  * @file
  * @author Egor
  */

require_once( dirname( __FILE__ ) . "/../../../maintenance/Maintenance.php" );

class TPCEvalThemeRedirects extends Maintenance {
	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		foreach ( array_unique( TouhouThemeDB\Title::REDIRECTS ) as $v ) {
			foreach ( Title::makeTitle( NS_THEMEDB, $v )->getSubPages() as $subpage ) {
				$this->output( $subpage->getPrefixedText() . "\n" );
				TouhouPatchCenter::evalTitle( $subpage );
			}
		}
	}
}

$maintClass = 'TPCEvalThemeRedirects';
require_once( RUN_MAINTENANCE_IF_MAIN );
