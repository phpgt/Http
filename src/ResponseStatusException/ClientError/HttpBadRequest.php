<?php
namespace GT\Http\ResponseStatusException;

use GT\Http\StatusCode;

/**
 * The server cannot or will not process the request due to an apparent client
 * error (e.g., malformed request syntax, size too large, invalid request
 * message framing, or deceptive request routing).
 * @link https://httpstatuses.com/400
 */
class HttpBadRequest extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::BAD_REQUEST;
	}
}
