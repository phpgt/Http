<?php
namespace GT\Http\Test;

use GT\Http\Header\HeaderLine;
use GT\Http\Header\RequestHeaders;
use GT\Http\Request;
use GT\Http\RequestMethod;
use GT\Http\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class MessageTest extends TestCase {
	/** @dataProvider data_request */
	public function testGetProtocolVersionRequest(
		string $method,
		string $uriString,
		array $headersArray
	) {
		/** @var MockObject|Uri $uri */
		$uri = self::createMock(UriInterface::class);
		$uri->method("__toString")
			->willReturn($uriString);

		/** @var MockObject|RequestHeaders $headers */
		$headers = self::createMock(RequestHeaders::class);
		$headers->method("getFirst")
			->willReturn($headersArray[0]);

		$request = new Request(
			$method,
			$uri,
			$headers
		);

		$expected = substr(
			$headersArray[0],
			strpos($headersArray[0], "/") + 1
		);
		$expected = substr(
			$expected,
			0,
			strpos($expected, " ")
		);

		$protocolVersion = $request->getProtocolVersion();
		self::assertEquals($expected, $protocolVersion);
	}

	public function testGetHeaderLine() {
		$headerName = "x-example";
		$headerValue = uniqid();

		$headerLine = self::createMock(HeaderLine::class);
		$headerLine->expects(self::once())
			->method("getValuesCommaSeparated")
			->willReturn($headerValue);

		$uri = self::createMock(Uri::class);
		$headers = self::createMock(RequestHeaders::class);
		$headers->expects(self::once())
			->method("get")
			->with($headerName)
			->willReturn($headerLine);

		$sut = new Request("GET", $uri, $headers);
		self::assertSame($headerValue, $sut->getHeaderLine($headerName));
	}

	public static function data_request():array {
		$data = [];

		for($i = 0; $i < 10; $i++) {
			$method = RequestMethod::ALLOWED_METHODS[
				array_rand(RequestMethod::ALLOWED_METHODS)
			];
			$uriString = self::generateUri();
			$headersArray = self::generateHeaders();

			$data []= [
				$method->name,
				$uriString,
				$headersArray,
			];
		}

		return $data;
	}

	public static function generateUri():string {
		$uri = "http";
		if(rand(0, 1)) {
			$uri .= "s";
		}
		$uri .= "://";

		$uri .= uniqid("www.");
		$uri .= ".com";

		if(rand(0, 1)) {
			$pathParts = rand(1, 10);
			for($i = 0; $i < $pathParts; $i++) {
				$uri .= "/";
				$uri .= uniqid();
			}
		}

		if(rand(0, 1)) {
			$uri .= "?";
			$qsParts = rand(1, 10);
			for($i = 0; $i < $qsParts; $i++) {
				if($i > 0) {
					$uri .= "&";
				}

				$uri .= uniqid("key-");
				$uri .= "=";
				$uri .= uniqid("value-");
			}
		}

		return $uri;
	}

	public static function generateHeaders():array {
		$headers = [];

		$firstLine = "";
		if(rand(0, 1)) {
			$firstLine .= strtoupper(
				substr(uniqid(), 0, 4)
			);
			$firstLine .= "/";
			$firstLine .= rand(1, 9);
		}
		else {
			$firstLine .= "HTTP";
			$firstLine .= "/";
			if(rand(0, 1)) {
				$firstLine .= "1.1";
			}
			else {
				$firstLine .= rand(2, 3);
			}
		}

		$firstLine .= " ";
		$firstLine .= rand(200, 550);
		$firstLine .= " ";
		$firstLine .= "TEST";
		$headers []= $firstLine;

		for($i = 0; $i < rand(1, 10); $i++) {
			$line = uniqid("Key-");
			$line .= ": ";
			$line .= uniqid("value-");
			$headers []= $line;
		}

		return $headers;
	}
}
