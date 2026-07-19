<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;
use GT\Http\ResponseStatusException\ResponseStatusException;

class HttpConflict extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::CONFLICT;
	}
}
