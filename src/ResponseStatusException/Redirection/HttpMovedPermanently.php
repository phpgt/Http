<?php
namespace GT\Http\ResponseStatusException\Redirection;

use GT\Http\StatusCode;

/**
 * This and all future requests should be directed to the given URI.
 * @link https://httpstatuses.com/301
 */
class HttpMovedPermanently extends RedirectionException {
	public function getHttpCode():int {
		return StatusCode::MOVED_PERMANENTLY;
	}
}
