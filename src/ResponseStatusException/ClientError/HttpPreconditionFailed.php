<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

class HttpPreconditionFailed extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::PRECONDITION_FAILED;
	}
}
