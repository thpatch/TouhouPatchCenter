<?php
class ApiEvalTitle extends ApiBase {
	public function execute() {
		$params = $this->extractRequestParams();

		$page = $this->getTitleOrPageId( $params, 'fromdbmaster' );
		$title = $page->getTitle();
		if ( !$title->exists() ) {
			$this->dieUsageMsg( 'notanarticle' );
		}

		PageTranslationHooks::$allowTargetEdit = true;
		$errors = $title->getUserPermissionsErrors( 'edit', $this->getUser() );
		PageTranslationHooks::$allowTargetEdit = false;
		if ( $errors ) {
			$this->dieUsageMsg( reset( $errors ) );
		}

		TouhouPatchCenter::evalTitle( $title );
		return true;
	}

	public function isWriteMode() {
		return true;
	}
	public function mustBePosted() {
		return true;
	}
	public function needsToken() {
		return 'csrf';
	}

	public function getDescription() {
		return 'TouhouPatchCenter: Evaluate a title';
	}
	public function getExamples() {
		return array( 'api.php?action=evaltitle&title=Main%20Page&token=123ABC' );
	}
	public function getAllowedParams() {
		return array(
			'title' => array( ApiBase::PARAM_TYPE => 'string' ),
			'pageid' => array( ApiBase::PARAM_TYPE => 'integer' ),
			'token' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			)
		);
	}
	public function getParamDescription() {
		return array(
			'title' => 'Title of the page to evaltitle. Cannot be used together with pageid',
			'pageid' => 'Page ID of the page to evaltitle. Cannot be used together with title',
			'token' => 'An edit/csrf token previously retrieved through prop=info, action=tokens or meta=tokens'
		);
	}
}
