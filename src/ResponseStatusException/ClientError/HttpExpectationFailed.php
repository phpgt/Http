<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;
use GT\Http\ResponseStatusException\ResponseStatusException;

class HttpExpectationFailed extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::EXPECTATION_FAILED;
	}
}
