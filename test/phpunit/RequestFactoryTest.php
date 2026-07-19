<?php
namespace GT\Http\Test;

use GT\Http\InvalidRequestMethodHttpException;
use GT\Http\RequestFactory;
use PHPUnit\Framework\TestCase;

class RequestFactoryTest extends TestCase {
	public function testCreateServerRequestFromGlobals_noRequestMethod():void {
		$sut = new RequestFactory();
		self::expectException(InvalidRequestMethodHttpException::class);
		$sut->createServerRequestFromGlobalState([], [], [], []);
	}

	public function testCreateServerRequestFromGlobals_invalidRequestMethod():void {
		$sut = new RequestFactory();
		self::expectException(InvalidRequestMethodHttpException::class);
		$sut->createServerRequestFromGlobalState([
			"REQUEST_METHOD" => "invalid"
		], [], [], []);
	}

	public function testCreateServerRequestFromGlobals_checkRequestMethod():void {
		$sut = new RequestFactory();
		$request = $sut->createServerRequestFromGlobalState([
			"REQUEST_METHOD" => "POST"
		], [], [], []);

		self::assertEquals("POST", $request->getMethod());
	}

	public function testCreateServerRequestFromGlobals_emptyUri():void {
		$sut = new RequestFactory();
		$request = $sut->createServerRequestFromGlobalState([
			"REQUEST_METHOD" => "POST"
		], [], [], []);

		self::assertEquals("", $request->getUri()->getPath());
	}

	public function testCreateServerRequestFromGlobals_uri():void {
		$sut = new RequestFactory();
		$request = $sut->createServerRequestFromGlobalState([
			"REQUEST_METHOD" => "POST",
			"REQUEST_URI" => "/path/to/somewhere"
		], [], [], []);

		self::assertEquals("/path/to/somewhere", $request->getUri()->getPath());
	}

	public function testCreateServerRequestFromGlobals_uri_withAllParts():void {
		$sut = new RequestFactory();
		$request = $sut->createServerRequestFromGlobalState([
			"REQUEST_METHOD" => "POST",
			"REQUEST_URI" => "/path/to/somewhere/",
			"SERVER_PORT" => 8080,
			"HTTP_HOST" => "localhost:8080",
			"QUERY_STRING" => "example=123",
		], [], [], []);

		self::assertSame(
			"http://localhost:8080/path/to/somewhere/?example=123",
			(string)$request->getUri()
		);
	}

	public function testCreateServerRequestFromGlobals_header():void {
		$sut = new RequestFactory();
		$request = $sut->createServerRequestFromGlobalState([
			"REQUEST_METHOD" => "GET",
			"HTTP_ACCEPT" => "*/*",
			"HTTP_HOST" => "PHPUnit"
		], [], [], []);
		self::assertEquals("*/*", $request->getHeaderLine("Accept"));
		self::assertEquals("PHPUnit", $request->getHeaderLine("Host"));
	}

	public function testCreateServerRequestFromGlobals_postBody():void {
		$message = "Hello, PHPUnit!";
		$tmp = tempnam(sys_get_temp_dir(), "phpgt-");

		file_put_contents($tmp, $message);

		$sut = new RequestFactory();
		$request = $sut->createServerRequestFromGlobalState([
			"REQUEST_METHOD" => "POST",
		], [], [], [], $tmp);
		$body = $request->getBody();
		$content = $body->read(100);
		unlink($tmp);

		self::assertEquals($message, $content);
	}

	public function testCreateServerRequestFromGlobals_headers():void {
		$sut = new RequestFactory();
		$request = $sut->createServerRequestFromGlobalState([
			"REQUEST_METHOD" => "POST",
			"SERVER_PROTOCOL" => "HTTP/1.1"
		], [], [], []);
		self::assertEquals("1.1", $request->getProtocolVersion());
	}

	public function testBuildRequestHeaders_hyphenated():void {
		$server = [
			"HTTP_ACCEPT" => "application/json",
			"HTTP_ACCEPT_LANGUAGE" => "en-GB",
			"HTTP_X_KEY" => "abc123",
		];

		$sut = new RequestFactory();
		$requestHeaders = $sut->buildRequestHeaders($server);

		foreach($server as $key => $value) {
			$keyWithoutHttp = substr($key, strlen("HTTP_"));
			$keyHyphenated = str_replace("_", "-", $keyWithoutHttp);
			self::assertEquals($value, $requestHeaders->get($keyHyphenated));
		}
	}
}
