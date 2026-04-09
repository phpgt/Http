<?php
namespace Gt\Http\ResponseStatusException\ClientError;

use Gt\Http\StatusCode;
use Gt\Http\ResponseStatusException\ResponseStatusException;

class HttpLengthRequired extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::LENGTH_REQUIRED;
	}
}
