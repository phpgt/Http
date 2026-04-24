<?php
namespace GT\Http\ResponseStatusException;

use GT\Http\StatusCode;

/**
 * A request method is not supported for the requested resource; for example, a
 * GET request on a form that requires data to be presented via POST, or a PUT
 * request on a read-only resource.
 * @link https://httpstatuses.com/405
 */
class HttpMethodNotAllowed extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::METHOD_NOT_ALLOWED;
	}
}
