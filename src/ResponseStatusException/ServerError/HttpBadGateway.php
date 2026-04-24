<?php
namespace GT\Http\ResponseStatusException;

use GT\Http\StatusCode;

/**
 * The server was acting as a gateway or proxy and received an invalid response
 * from the upstream server.
 * @link https://httpstatuses.com/502
 */
class HttpBadGateway extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::BAD_GATEWAY;
	}
}
