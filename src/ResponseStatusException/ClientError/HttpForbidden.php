<?php
namespace Gt\Http\ResponseStatusException\ClientError;

use Gt\Http\StatusCode;
use Gt\Http\ResponseStatusException\ResponseStatusException;

/**
 * The request contained valid data and was understood by the server, but the
 * server is refusing action. This may be due to the user not having the
 * necessary permissions for a resource or needing an account of some sort, or
 * attempting a prohibited action (e.g. creating a duplicate record where only
 * one is allowed). This code is also typically used if the request provided
 * authentication via the WWW-Authenticate header field, but the server did not
 * accept that authentication. The request should not be repeated.
 * @link https://httpstatuses.com/403
 */
class HttpForbidden extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::FORBIDDEN;
	}
}
