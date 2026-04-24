<?php
namespace GT\Http\ResponseStatusException\Redirection;

use GT\Http\StatusCode;

/**
 * Indicates multiple options for the resource from which the client may choose
 * (via agent-driven content negotiation). For example, this code could be used
 * to present multiple video format options, to list files with different
 * filename extensions, or to suggest word-sense disambiguation.
 * @link https://httpstatuses.com/300
 */
class HttpMultipleChoices extends RedirectionException {
	public function getHttpCode():int {
		return StatusCode::MULTIPLE_CHOICES;
	}
}
