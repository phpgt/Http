<?php
namespace GT\Http;

use Negotiation\Accept;
use Negotiation\BaseAccept;
use Negotiation\Exception\InvalidArgument;
use Negotiation\Negotiator;
use Psr\Http\Message\RequestInterface;

class ResponseFactory {
	const DEFAULT_ACCEPT = "text/html";
	/** @var array<string, string> key=mime type, value=class name */
	protected static array $responseClassLookup = [];
	protected static ?BaseAccept $mediaType;

	/**
	 * A Response object is a PSR-7 compatible object that is created here
	 * from the current Request. The type of Response that is returned is
	 * determined by the type of request.
	 *
	 * @see http://www.php-fig.org/psr/psr-7
	 */
	public static function create(RequestInterface $request):Response {
		$negotiator = new Negotiator();
		$acceptHeader = $request->getHeaderLine("accept")
			?: self::DEFAULT_ACCEPT;
		$priorities = [
			"text/html; charset=UTF-8",
			"application/json",
			"application/xml;q=0.5",
		];

		/** @var ?Accept $best */
		$best = $negotiator->getBest(
			$acceptHeader,
			$priorities
		);
		static::$mediaType = $best;

		if(static::$mediaType) {
			$accept = static::$mediaType->getType();
		}
		else {
			$accept = $acceptHeader;
		}

		if(!isset(static::$responseClassLookup[$accept])) {
			throw new UnknownAcceptHeaderException($accept);
		}

		/** @var Response $response */
		$response = new static::$responseClassLookup[$accept];
		return $response;
	}

	/**
	 * In order for this factory to create the correct type of Response,
	 * class names need to be registered to match the request's "accept"
	 * header. This allows the web client to request a web page in HTML
	 * format, over a web API in JSON format, for example.
	 */
	public static function registerResponseClass(
		string $responseClassName,
		string...$accept
	):void {
		if(empty($accept)) {
			$accept = [self::DEFAULT_ACCEPT];
		}

		foreach($accept as $a) {
			static::$responseClassLookup [$a] = $responseClassName;
		}
	}
}
