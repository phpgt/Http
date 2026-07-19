<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

class HttpRequestHeaderFieldsTooLarge extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::REQUEST_HEADER_FIELDS_TOO_LARGE;
	}
}
