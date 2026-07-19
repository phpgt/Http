<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

class HttpMisdirectedRequest extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::MISDIRECTED_REQUEST;
	}
}
