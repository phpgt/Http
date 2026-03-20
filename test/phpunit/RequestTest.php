<?php
namespace Gt\Http\Test;

use Gt\Http\FormData;
use Gt\Http\Header\RequestHeaders;
use Gt\Http\InvalidRequestMethodHttpException;
use Gt\Http\Request;
use Gt\Http\RequestMethod;
use Gt\Http\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase {
	public function testGetRequestTarget() {
		$uriPath = "/test?key1=val1&key2=val2";
		$sut = new Request(
			"get",
			self::getUriMock($uriPath),
			self::getHeadersMock()
		);
		self::assertEquals($uriPath, $sut->getRequestTarget());
	}

	public function testGetRequestTargetEmpty() {
		$sut = new Request(
			"get",
			self::getUriMock(),
			self::getHeadersMock()
		);
		self::assertEquals("/", $sut->getRequestTarget());
	}

	public function testWithRequestTarget() {
		$req = new Request(
			"get",
			self::getUriMock("/"),
			self::getHeadersMock()
		);
		$target = "https://example.com?key1=val1";
		$sut = $req->withRequestTarget($target);
		self::assertEquals($target, $sut->getRequestTarget());
	}

	public function testGetMethod() {
		$req = new Request(
			"get",
			self::getUriMock(),
			self::getHeadersMock()
		);
		self::assertEquals(
			RequestMethod::GET->name,
			$req->getMethod()
		);
	}

	public function testWithMethod() {
		$req = new Request(
			"get",
			self::getUriMock(),
			self::getHeadersMock()
		);
		$sut = $req->withMethod("post");
		self::assertEquals(
			RequestMethod::POST->name,
			$sut->getMethod()
		);
	}

	public function testInvalidMethod() {
		self::expectException(InvalidRequestMethodHttpException::class);
		$sut = new Request(
			"unknown",
			self::getUriMock(),
			self::getHeadersMock()
		);
	}

	public function testWithUri() {
		$req = new Request(
			"get",
			self::getUriMock("/one/two/three"),
			self::getHeadersMock()
		);
		$sut = $req->withUri(self::getUriMock("/four/five/six"));
		self::assertEquals("/four/five/six", $sut->getUri());
	}

	public function testWithUriNewHost() {
		$headers = self::getHeadersMock();
		$headers->expects($this->once())
			->method("add")
			->with("Host", "example2.com");
		$req = new Request(
			"get",
			self::getUriMock("https://example.com/test"),
			$headers
		);
		$req->withUri(self::getUriMock("https://example2.com/something"));
	}

	public function testWithFormDataBodyAutomaticallySetsContentTypeHeader():void {
		$formData = new FormData();
		$formData->append("key", "value");

		$request = new Request(
			"POST",
			self::getUriMock("/"),
			new RequestHeaders()
		);

		$request = $request->withBody($formData);
		self::assertSame(
			"application/x-www-form-urlencoded",
			$request->getHeaderLine("Content-Type")
		);
	}

	public function testGetHeaderReturnsAllValuesAcrossSeparateHeaderLines():void {
		$headers = new RequestHeaders();
		$headers->add("WWW-Authenticate", "Digest realm=\"example.com\", qop=\"auth\"");
		$headers->add("WWW-Authenticate", "Digest realm=\"api.example.com\", qop=\"auth-int\"");

		$request = new Request(
			"GET",
			self::getUriMock("/"),
			$headers
		);

		self::assertSame(
			[
				"Digest realm=\"example.com\", qop=\"auth\"",
				"Digest realm=\"api.example.com\", qop=\"auth-int\"",
			],
			$request->getHeader("WWW-Authenticate")
		);
	}

	/** @return MockObject|Uri */
	protected function getUriMock(string $uriPath = ""):MockObject {
		$partPath = parse_url($uriPath, PHP_URL_PATH);
		$partQuery = parse_url($uriPath, PHP_URL_QUERY);
		$partHost = parse_url($uriPath, PHP_URL_HOST);
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")
			->willReturn($partPath ?? "");
		$uri->method("getQuery")
			->willReturn($partQuery ?? "");
		$uri->method("getHost")
			->willReturn($partHost ?? "");
		$uri->method("__toString")
			->willReturn($partPath ?? "");
		return $uri;
	}

	/** @return MockObject|RequestHeaders */
	protected function getHeadersMock():MockObject {
		$headers = self::createMock(RequestHeaders::class);
		return $headers;
	}
}
