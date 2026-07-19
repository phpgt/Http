<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;
use GT\Http\ResponseStatusException\ResponseStatusException;

class HttpFailedDependency extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::FAILED_DEPENDENCY;
	}
}
