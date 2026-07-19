<?php
namespace GT\Http\ResponseStatusException\ClientError;

use GT\Http\StatusCode;
use GT\Http\ResponseStatusException\ResponseStatusException;

class HttpImATeapot extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::IM_A_TEAPOT;
	}
}
