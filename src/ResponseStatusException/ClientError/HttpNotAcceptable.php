<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

/**
 * The requested resource is capable of generating only content not acceptable
 * according to the Accept headers sent in the request.
 * @linkhttps://httpstatuses.com/406
 */
class HttpNotAcceptable extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::NOT_ACCEPTABLE;
	}
}
