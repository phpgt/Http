<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;

/**
 * The request was well-formed but was unable to be followed due to semantic
 * errors.
 * @link https://httpstatuses.com/422
 */
class HttpUnprocessableEntity extends ClientErrorException {
	public function getHttpCode():int {
		return StatusCode::UNPROCESSABLE_ENTITY;
	}
}
