<?php
namespace GT\Http;

use InvalidArgumentException;

class StatusCode {
	const int CONTINUE = 100;
	const int SWITCHING_PROTOCOLS = 101;
	const int PROCESSING = 102;
	const int EARLY_HINTS = 103;

	const int OK = 200;
	const int CREATED = 201;
	const int ACCEPTED = 202;
	const int NON_AUTHORITATIVE_INFORMATION = 203;
	const int NO_CONTENT = 204;
	const int RESET_CONTENT = 205;
	const int PARTIAL_CONTENT = 206;
	const int MULTI_STATUS = 207;
	const int ALREADY_REPORTED = 208;
	const int IM_USED = 226;

	const int MULTIPLE_CHOICES = 300;
	const int MOVED_PERMANENTLY = 301;
	const int FOUND = 302;
	const int SEE_OTHER = 303;
	const int NOT_MODIFIED = 304;
	const int USE_PROXY = 305;
	const int SWITCH_PROXY = 306;
	const int TEMPORARY_REDIRECT = 307;
	const int PERMANENT_REDIRECT = 308;

	const int BAD_REQUEST = 400;
	const int UNAUTHORIZED = 401;
	const int PAYMENT_REQUIRED = 402;
	const int FORBIDDEN = 403;
	const int NOT_FOUND = 404;
	const int METHOD_NOT_ALLOWED = 405;
	const int NOT_ACCEPTABLE = 406;
	const int PROXY_AUTHENTICATION_REQUIRED = 407;
	const int REQUEST_TIME_OUT = 408;
	const int CONFLICT = 409;
	const int GONE = 410;
	const int LENGTH_REQUIRED = 411;
	const int PRECONDITION_FAILED = 412;
	const int PAYLOAD_TOO_LARGE = 413;
	const int REQUEST_URI_TOO_LONG = 414;
	const int UNSUPPORTED_MEDIA_TYPE = 415;
	const int REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	const int EXPECTATION_FAILED = 417;
	const int IM_A_TEAPOT = 418;
	const int MISDIRECTED_REQUEST = 421;
	const int UNPROCESSABLE_ENTITY = 422;
	const int LOCKED = 423;
	const int FAILED_DEPENDENCY = 424;
	const int TOO_EARLY = 425;
	const int UPGRADE_REQUIRED = 426;
	const int PRECONDITION_REQUIRED = 428;
	const int TOO_MANY_REQUESTS = 429;
	const int REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
	const int UNAVAILABLE_FOR_LEGAL_REASONS = 451;

	const int INTERNAL_SERVER_ERROR = 500;
	const int NOT_IMPLEMENTED = 501;
	const int BAD_GATEWAY = 502;
	const int SERVICE_UNAVAILABLE = 503;
	const int GATEWAY_TIMEOUT = 504;
	const int HTTP_VERSION_NOT_SUPPORTED = 505;
	const int VARIANT_ALSO_NEGOTIATES = 506;
	const int INSUFFICIENT_STORAGE = 507;
	const int LOOP_DETECTED = 508;
	const int NOT_EXTENDED = 510;
	const int NETWORK_AUTHENTICATION_REQUIRED = 511;

	const array REASON_PHRASE = [
		self::CONTINUE => "Continue",
		self::SWITCHING_PROTOCOLS => "Switching Protocols",
		self::PROCESSING => "Processing",
		self::EARLY_HINTS => "Early Hints",

		self::OK => "OK",
		self::CREATED => "Created",
		self::ACCEPTED => "Accepted",
		self::NON_AUTHORITATIVE_INFORMATION => "Non-Authoritative Information",
		self::NO_CONTENT => "No Content",
		self::RESET_CONTENT => "Reset Content",
		self::PARTIAL_CONTENT => "Partial Content",
		self::MULTI_STATUS => "Multi-status",
		self::ALREADY_REPORTED => "Already Reported",
		self::IM_USED => "IM Used",

		self::MULTIPLE_CHOICES => "Multiple Choices",
		self::MOVED_PERMANENTLY => "Moved Permanently",
		self::FOUND => "Found",
		self::SEE_OTHER => "See Other",
		self::NOT_MODIFIED => "Not Modified",
		self::USE_PROXY => "Use Proxy",
		self::SWITCH_PROXY => "Switch Proxy",
		self::TEMPORARY_REDIRECT => "Temporary Redirect",
		self::PERMANENT_REDIRECT => "Permanent Redirect",

		self::BAD_REQUEST => "Bad Request",
		self::UNAUTHORIZED => "Unauthorized",
		self::PAYMENT_REQUIRED => "Payment Required",
		self::FORBIDDEN => "Forbidden",
		self::NOT_FOUND => "Not Found",
		self::METHOD_NOT_ALLOWED => "Method Not Allowed",
		self::NOT_ACCEPTABLE => "Not Acceptable",
		self::PROXY_AUTHENTICATION_REQUIRED => "Proxy Authentication Required",
		self::REQUEST_TIME_OUT => "Request Time-out",
		self::CONFLICT => "Conflict",
		self::GONE => "Gone",
		self::LENGTH_REQUIRED => "Length Required",
		self::PRECONDITION_FAILED => "Precondition Failed",
		self::PAYLOAD_TOO_LARGE => "Request Entity Too Large",
		self::REQUEST_URI_TOO_LONG => "Request-URI Too Large",
		self::UNSUPPORTED_MEDIA_TYPE => "Unsupported Media Type",
		self::REQUESTED_RANGE_NOT_SATISFIABLE => "Requested range not satisfiable",
		self::EXPECTATION_FAILED => "Expectation Failed",
		self::IM_A_TEAPOT => "I'm a teapot",
		self::MISDIRECTED_REQUEST => "Misdirected Request",
		self::UNPROCESSABLE_ENTITY => "Unprocessable Entity",
		self::LOCKED => "Locked",
		self::FAILED_DEPENDENCY => "Failed Dependency",
		self::TOO_EARLY => "Too Early",
		self::UPGRADE_REQUIRED => "Upgrade Required",
		self::PRECONDITION_REQUIRED => "Precondition Required",
		self::TOO_MANY_REQUESTS => "Too Many Requests",
		self::REQUEST_HEADER_FIELDS_TOO_LARGE => "Request Header Fields Too Large",
		self::UNAVAILABLE_FOR_LEGAL_REASONS => "Unavailable For Legal Reasons",

		self::INTERNAL_SERVER_ERROR => "Internal Server Error",
		self::NOT_IMPLEMENTED => "Not Implemented",
		self::BAD_GATEWAY => "Bad Gateway",
		self::SERVICE_UNAVAILABLE => "Service Unavailable",
		self::GATEWAY_TIMEOUT => "Gateway Time-out",
		self::HTTP_VERSION_NOT_SUPPORTED => "HTTP Version not supported",
		self::VARIANT_ALSO_NEGOTIATES => "Variant Also Negotiates",
		self::INSUFFICIENT_STORAGE => "Insufficient Storage",
		self::LOOP_DETECTED => "Loop Detected",
		self::NOT_EXTENDED => "Not Extended",
		self::NETWORK_AUTHENTICATION_REQUIRED => "Network Authentication Required",
	];

	private ?int $code = null;

	public function get():?int {
		return $this->code;
	}

	public function set(int $code):void {
		if($code < 100 || $code > 599) {
			throw new InvalidArgumentException(
				"HTTP status code must be between 100 and 599: $code"
			);
		}

		$this->code = $code;
	}

	public function reset():void {
		$this->code = null;
	}

	public function getReasonPhrase():string {
		return self::REASON_PHRASE[$this->code ?? 0] ?? "";
	}
}
