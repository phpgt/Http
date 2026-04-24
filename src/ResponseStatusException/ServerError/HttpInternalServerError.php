<?php
namespace GT\Http\ResponseStatusException\ServerError;

use GT\Http\StatusCode;

/**
 * A generic error message, given when an unexpected condition was encountered
 * and no more specific message is suitable.
 * @link https://httpstatuses.com/500
 */
class HttpInternalServerError extends ServerErrorException {
	public function getHttpCode():int {
		return StatusCode::INTERNAL_SERVER_ERROR;
	}
}
