<?php
namespace GT\Http\ResponseStatusException;

use GT\Http\StatusCode;

class HttpGone extends ResponseStatusException {
	public function getHttpCode():int {
		return StatusCode::GONE;
	}
}
