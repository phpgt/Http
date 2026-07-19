<?php
namespace GT\Http\ResponseStatusException\Redirection;

use GT\Http\StatusCode;

/**
 * The response to the request can be found under another URI using the GET
 * method. When received in response to a POST (or PUT/DELETE), the client
 * should presume that the server has received the data and should issue a new
 * GET request to the given URI.
 * @link https://httpstatuses.com/303
 */
class HttpSeeOther extends RedirectionException {
	public function getHttpCode():int {
		return StatusCode::SEE_OTHER;
	}
}
