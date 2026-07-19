<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;
use GT\Http\ResponseStatusException\ResponseStatusException;

class HttpLocked extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::LOCKED;
	}
}
