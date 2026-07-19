<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

/**
 * The client must first authenticate itself with the proxy.
 * @linkhttps://httpstatuses.com/407
 */
class HttpProxyAuthenticationRequired extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::PROXY_AUTHENTICATION_REQUIRED;
	}
}
