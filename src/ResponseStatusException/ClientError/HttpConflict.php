<?php
namespace Gt\Http\ResponseStatusException\ClientError;

use Gt\Http\StatusCode;
use Gt\Http\ResponseStatusException\ResponseStatusException;

class HttpConflict extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::CONFLICT;
	}
}
