<?php
namespace GT\Http\ResponseStatusException\ServerError;

use GT\Http\StatusCode;

/**
 * The client needs to authenticate to gain network access. Intended for use by
 * intercepting proxies used to control access to the network (e.g., "captive
 * portals" used to require agreement to Terms of Service before granting full
 * Internet access via a Wi-Fi hotspot).
 * @link https://httpstatuses.com/511
 */
class HttpNetworkAuthenticationRequired extends ServerErrorException {
	public function getHttpCode():int {
		return StatusCode::NETWORK_AUTHENTICATION_REQUIRED;
	}
}
