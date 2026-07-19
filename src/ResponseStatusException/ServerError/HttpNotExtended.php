<?php
namespace GT\Http\ResponseStatusException\ServerError;

use GT\Http\StatusCode;

/**
 * Further extensions to the request are required for the server to fulfil it.
 * @link https://httpstatuses.com/510
 */
class HttpNotExtended extends ServerErrorException {
	public function getHttpCode():int {
		return StatusCode::NOT_EXTENDED;
	}
}
