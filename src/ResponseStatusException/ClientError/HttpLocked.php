<?php
namespace GT\Http\ResponseStatusException;

use GT\Http\StatusCode;

class HttpLocked extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::LOCKED;
	}
}
