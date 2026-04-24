<?php
namespace GT\Http\ResponseStatusException;

use GT\Http\StatusCode;

class HttpImATeapot extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::IM_A_TEAPOT;
	}
}
