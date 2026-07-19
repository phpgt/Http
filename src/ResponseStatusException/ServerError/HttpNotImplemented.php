<?php
namespace GT\Http\ResponseStatusException\ServerError;

use GT\Http\StatusCode;

/**
 * The server either does not recognize the request method, or it lacks the
 * ability to fulfil the request. Usually this implies future availability
 * (e.g., a new feature of a web-service API).
 * @link https://httpstatuses.com/501
 */
class HttpNotImplemented extends ServerErrorException {
	public function getHttpCode():int {
		return StatusCode::NOT_IMPLEMENTED;
	}
}
