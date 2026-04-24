<?php
namespace GT\Http;

enum RequestMethod {
	case GET;
	case HEAD;
	case POST;
	case PUT;
	case OPTIONS;
	case DELETE;
	case TRACE;

	const ALLOWED_METHODS = [
		self::GET,
		self::HEAD,
		self::POST,
		self::PUT,
		self::OPTIONS,
		self::DELETE,
		self::TRACE,
	];

	public static function filterMethodName(string $name):string {
		$name = strtoupper($name);

		$found = false;
		foreach(self::cases() as $case) {
			if($name === $case->name) {
				$found = true;
				break;
			}
		}

		if(!$found) {
			throw new InvalidRequestMethodHttpException($name);
		}

		return $name;
	}
}
