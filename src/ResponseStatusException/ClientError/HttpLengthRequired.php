<?php
namespace GT\Http\ResponseStatusException;

use GT\Http\StatusCode;

class HttpLengthRequired extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::LENGTH_REQUIRED;
	}
}
