<?php

/**
  * Simply calls TouhouPatchCenter::evalPage on a given title. :-)
  *
  * @file
  * @author Nmlgc
  */

require_once( dirname( __FILE__ ) . "/../../../maintenance/Maintenance.php" );

class TPCEvalPage extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addOption( 'title', 'Title of the page to evaluate', true, false );
	}
	public function execute() {
		$title = $this->getOption( 'title' );
		if ( !$title ) {
			return;
		}
		TouhouPatchCenter::evalPage( Title::newFromText( $title ) );
		return;
	}
}

$maintClass = 'TPCEvalPage';
require_once( RUN_MAINTENANCE_IF_MAIN );
