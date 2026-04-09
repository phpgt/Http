<?php
namespace Gt\Http\ResponseStatusException\ClientError;

use Gt\Http\StatusCode;
use Gt\Http\ResponseStatusException\ResponseStatusException;

class HttpImATeapot extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::IM_A_TEAPOT;
	}
}
