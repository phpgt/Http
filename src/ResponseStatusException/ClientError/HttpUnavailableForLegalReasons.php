<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

/**
 * A server operator has received a legal demand to deny access to a resource
 * or to a set of resources that includes the requested resource. The code 451
 * was chosen as a reference to the novel Fahrenheit 451.
 * @link https://httpstatuses.com/451
 */
class HttpUnavailableForLegalReasons extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::UNAVAILABLE_FOR_LEGAL_REASONS;
	}
}
