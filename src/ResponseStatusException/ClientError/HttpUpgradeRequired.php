<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

/**
 * The client should switch to a different protocol such as TLS/1.0, given in
 * the Upgrade header field.
 * @link https://httpstatuses.com/426
 */
class HttpUpgradeRequired extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::UPGRADE_REQUIRED;
	}
}
