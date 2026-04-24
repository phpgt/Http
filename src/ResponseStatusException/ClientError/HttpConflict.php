<?php
namespace GT\Http\ResponseStatusException;

use GT\Http\StatusCode;

class HttpConflict extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::CONFLICT;
	}
}
