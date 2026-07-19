<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;
use GT\Http\ResponseStatusException\ResponseStatusException;

class HttpLengthRequired extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::LENGTH_REQUIRED;
	}
}
