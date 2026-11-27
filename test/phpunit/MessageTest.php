<?php
namespace GT\Http\Test;

use GT\Http\Header\HeaderLine;
use GT\Http\Header\RequestHeaders;
use GT\Http\Header\ResponseHeaders;
use GT\Http\Response;
use GT\Http\Stream;
use GT\Http\InvalidProtocolHttpException;
use GT\Http\Request;
use GT\Http\RequestMethod;
use GT\Http\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class MessageTest extends TestCase {
	#[DataProvider("data_request")]
	public function testGetProtocolVersionRequest(
		string $method,
		string $uriString,
		array $headersArray
	) {
		/** @var Stub|UriInterface $uri */
		$uri = self::createStub(UriInterface::class);
		$uri->method("__toString")
			->willReturn($uriString);

		/** @var Stub|RequestHeaders $headers */
		$headers = self::createStub(RequestHeaders::class);
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

		$uri = self::createStub(Uri::class);
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
	public function testProtocolVersionChangeLeavesOriginalUntouched():void {
		$original = (new Response())->withProtocolVersion("1.1");
		$updated = $original->withProtocolVersion("2");
		self::assertNotSame($original, $updated);
		self::assertSame("1.1", $original->getProtocolVersion());
		self::assertSame("2", $updated->getProtocolVersion());
	}

	public function testInvalidProtocolVersionIsRejected():void {
		$response = (new Response())->withProtocolVersion("1.1");
		try {
			$response->withProtocolVersion("invalid");
			self::fail("Invalid protocol must be rejected");
		}
		catch(InvalidProtocolHttpException) {
			self::assertSame("1.1", $response->getProtocolVersion());
		}
	}

	public function testHeaderLookupIsCaseInsensitiveAndMissingHeadersAreEmpty():void {
		$headers = new ResponseHeaders();
		$headers->add("X-Example", "first", "second");
		$response = new Response(headers: $headers);
		self::assertTrue($response->hasHeader("x-example"));
		self::assertFalse($response->hasHeader("Missing"));
		self::assertSame(["first", "second"], $response->getHeader("X-EXAMPLE"));
		self::assertSame([], $response->getHeader("Missing"));
		self::assertSame("", $response->getHeaderLine("Missing"));
		self::assertSame(["X-Example" => ["first", "second"]], $response->getHeaders());
	}

	public function testReplacingBodyPreservesOriginalStream():void {
		$response = new Response();
		$originalBody = $response->getBody();
		$originalBody->write("original");
		$replacement = new Stream();
		$replacement->write("replacement");
		$updated = $response->withBody($replacement);
		self::assertSame($originalBody, $response->getBody());
		self::assertSame($replacement, $updated->getBody());
		self::assertSame("original", (string)$response->getBody());
		self::assertSame("replacement", (string)$updated->getBody());
	}
}
