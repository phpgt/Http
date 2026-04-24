<?php
namespace GT\Http\ResponseStatusException;

use GT\Http\StatusCode;

class HttpExpectationFailed extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::EXPECTATION_FAILED;
	}
}
