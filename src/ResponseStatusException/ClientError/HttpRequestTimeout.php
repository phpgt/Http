<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

/**
 * The server timed out waiting for the request. According to HTTP
 * specifications: "The client did not produce a request within the time that
 * the server was prepared to wait. The client MAY repeat the request without
 * modifications at any later time."
 * @linkhttps://httpstatuses.com/408
 */
class HttpRequestTimeout extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::REQUEST_TIME_OUT;
	}
}
