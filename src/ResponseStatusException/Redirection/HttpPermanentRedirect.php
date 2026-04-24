<?php
namespace GT\Http\ResponseStatusException\Redirection;

use GT\Http\StatusCode;

/**
 * The request and all future requests should be repeated using another URI.
 * 307 and 308 parallel the behaviors of 302 and 301, but do not allow the HTTP
 * method to change. So, for example, submitting a form to a permanently
 * redirected resource may continue smoothly.
 * @link https://httpstatuses.com/308
 */
class HttpPermanentRedirect extends RedirectionException {
	public function getHttpCode():int {
		return StatusCode::PERMANENT_REDIRECT;
	}
}
