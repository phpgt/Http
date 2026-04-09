<?php
namespace Gt\Http\ResponseStatusException\ClientError;

use Gt\Http\StatusCode;
use Gt\Http\ResponseStatusException\ResponseStatusException;

class HttpFailedDependency extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::FAILED_DEPENDENCY;
	}
}
