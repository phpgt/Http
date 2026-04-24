<?php
namespace GT\Http\ResponseStatusException\Redirection;

use GT\Http\StatusCode;

/**
 * Indicates that the resource has not been modified since the version specified
 * by the request headers If-Modified-Since or If-None-Match. In such case,
 * there is no need to retransmit the resource since the client still has a
 * previously-downloaded copy.
 * @link https://httpstatuses.com/304
 */
class HttpNotModified extends RedirectionException {
	public function getHttpCode():int {
		return StatusCode::NOT_MODIFIED;
	}
}
