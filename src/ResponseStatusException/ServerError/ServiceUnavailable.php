<?php
namespace GT\Http\ResponseStatusException\ServerError;

use GT\Http\StatusCode;

/**
 * The server cannot handle the request (because it is overloaded or down for
 * maintenance). Generally, this is a temporary state.
 * @link https://httpstatuses.com/503
 */
class ServiceUnavailable extends ServerErrorException {
	public function getHttpCode():int {
		return StatusCode::SERVICE_UNAVAILABLE;
	}
}
