<?php
namespace GT\Http\ResponseStatusException\Redirection;

use GT\Http\StatusCode;

/**
 * In this case, the request should be repeated with another URI; however,
 * future requests should still use the original URI. In contrast to how 302 was
 * historically implemented, the request method is not allowed to be changed
 * when reissuing the original request. For example, a POST request should be
 * repeated using another POST request.
 * @link https://httpstatuses.com/307
 */
class HttpTemporaryRedirect extends RedirectionException {
	public function getHttpCode():int {
		return StatusCode::TEMPORARY_REDIRECT;
	}
}
