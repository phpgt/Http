<?php
namespace GT\Http\ResponseStatusException;

use GT\Http\StatusCode;

class HttpFailedDependency extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::FAILED_DEPENDENCY;
	}
}
