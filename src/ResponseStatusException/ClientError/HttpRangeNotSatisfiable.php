<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

class HttpRangeNotSatisfiable extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::REQUESTED_RANGE_NOT_SATISFIABLE;
	}
}
