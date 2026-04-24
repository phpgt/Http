<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

class HttpPayloadTooLarge extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::PAYLOAD_TOO_LARGE;
	}
}
