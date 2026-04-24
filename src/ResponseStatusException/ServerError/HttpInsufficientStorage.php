<?php
namespace GT\Http\ResponseStatusException\ServerError;

use GT\Http\StatusCode;

/**
 * The server is unable to store the representation needed to complete the
 * request.
 * @link https://httpstatuses.com/507
 */
class HttpInsufficientStorage extends ServerErrorException {
	public function getHttpCode():int {
		return StatusCode::INSUFFICIENT_STORAGE;
	}
}
