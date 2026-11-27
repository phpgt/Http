<?php
namespace GT\Http\Test;

use GT\Curl\Curl;
use GT\Http\ArrayBuffer;
use GT\Http\Blob;
use GT\Http\FormData;
use GT\Http\HttpException;
use GT\Http\IntegrityMismatchException;
use GT\Http\InvalidIntegrityAlgorithmException;
use GT\Http\Header\ResponseHeaders;
use GT\Http\Request;
use GT\Http\Response;
use GT\Http\StatusCode;
use GT\Http\Stream;
use GT\Http\Uri;
use GT\Json\JSONObject;
use GT\Promise\Deferred;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase {
	public function testGetStatusCode() {
		$sut = new Response(123);
		self::assertEquals(123, $sut->getStatusCode());
	}

	public function testWithStatusCode() {
		$sut = new Response(123);
		self::assertEquals(
			321,
			$sut->withStatus(321)->getStatusCode()
		);
	}

	public function testSetStatus() {
		$sut = new Response(123);
		$sut->setStatus(321);
		self::assertSame(321, $sut->getStatusCode());
	}

	public function testAbort() {
		$called = false;
		$exitCallback = function()use(&$called) {
			$called = true;
		};

		$sut = new Response();
		$sut->setExitCallback($exitCallback);
		$sut->abort();
		self::assertTrue($called);
	}

	public function testAbort_withNewStatus() {
		$called = false;
		$exitCallback = function()use(&$called) {
			$called = true;
		};

		$sut = new Response(500);
		$sut->setExitCallback($exitCallback);
		$sut->abort(503);
		self::assertTrue($called);
		self::assertSame(503, $sut->getStatusCode());
	}

	public function testGetReasonPhraseDefault() {
		$notFound = new Response(404);
		self::assertEquals("Not Found", $notFound->getReasonPhrase());

		$noContent = new Response(204);
		self::assertEquals("No Content", $noContent->getReasonPhrase());

		$teapot = new Response(418);
		self::assertEquals("I'm a teapot", $teapot->getReasonPhrase());
	}

	public function testGetResponseHeadersDefault() {
		$sut = new Response(123);
		$headers = $sut->getResponseHeaders();
		self::assertInstanceOf(ResponseHeaders::class, $headers);
		self::assertCount(0, $headers);
	}

	public function testRedirect() {
		$callbackCount = 0;
		$callback = function()use(&$callbackCount) {
			$callbackCount++;
		};

		$sut = new Response(200);
		$sut->setExitCallback($callback);

		self::assertSame(0, $callbackCount);
		$sut->redirect("/somewhere/");
		self::assertSame(1, $callbackCount);

		self::assertSame(
			"/somewhere/",
			$sut->getHeaderLine("Location")
		);
	}

	public function testReloadKeepsQuery() {
		$expectedRelativePath = "./?test=123";

		$uri = self::createMock(Uri::class);
		$uri->expects(self::never())
			->method("withQuery");
		$uri->method("__toString")
			->willReturn($expectedRelativePath);

		$request = self::createStub(Request::class);
		$request->method("getUri")
			->willReturn($uri);

		$sut = new Response(200, request: $request);
		self::assertSame(StatusCode::OK, $sut->getStatusCode());
		$sut->reload();
		self::assertSame(StatusCode::SEE_OTHER, $sut->getStatusCode());
		self::assertSame($expectedRelativePath, $sut->getHeaderLine("Location"));
	}

	public function testReloadWithoutQuery() {
		$expectedRelativePath = "./";

		$uri = self::createMock(Uri::class);
		$uri->expects(self::once())
			->method("withQuery")
			->with("")
			->willReturn($uri);
		$uri->method("__toString")
			->willReturn($expectedRelativePath);

		$request = self::createStub(Request::class);
		$request->method("getUri")
			->willReturn($uri);

		$sut = new Response(200, request: $request);
		$sut->reloadWithoutQuery();
		self::assertSame(StatusCode::SEE_OTHER, $sut->getStatusCode());
		self::assertSame($expectedRelativePath, $sut->getHeaderLine("Location"));
	}

	public function testArrayBuffer():void {
		$bodyData = random_bytes(1050);
		$stream = new Stream();
		$stream->write($bodyData);

		$sut = new Response();
		$sut = $sut->withBody($stream);

		$actualArrayBuffer = null;
		$sut->arrayBuffer()->then(function($arrayBuffer) use(&$actualArrayBuffer) {
			$actualArrayBuffer = $arrayBuffer;
		});

		self::assertInstanceOf(ArrayBuffer::class, $actualArrayBuffer);
		self::assertSame(1050, $actualArrayBuffer->byteLength);
	}

	public function testAsyncArrayBuffer():void {
		$bodyData = random_bytes(1050);
		$stream = new Stream();
		$stream->write($bodyData);

		$sut = new Response();
		$sut = $sut->withBody($stream);

		$arrayBuffer = $sut->awaitArrayBuffer();
		self::assertInstanceOf(ArrayBuffer::class, $arrayBuffer);
		self::assertSame(1050, $arrayBuffer->byteLength);
	}

	public function testJson():void {
		$jsonString = json_encode(["name" => "phpgt"]);

		$actualJson = null;

		$stream = new Stream();
		$stream->write($jsonString);

		$sut = (new Response())->withBody($stream);

		$sut->json()->then(function(JSONObject $json) use(&$actualJson) {
			$actualJson = $json;
		});

		self::assertSame("phpgt", $actualJson->getString("name"));
	}

	public function testAwaitJson():void {
		$jsonString = json_encode(["name" => "phpgt"]);

		$stream = new Stream();
		$stream->write($jsonString);

		$sut = (new Response())->withBody($stream);

		$actualJson = $sut->awaitJson();
		self::assertSame("phpgt", $actualJson->getString("name"));
	}

	public function testBlob():void {
		$blobString = random_bytes(32);

		$stream = new Stream();
		$stream->write($blobString);

		$sut = (new Response())->withBody($stream);

		$actualBlob = null;
		$sut->blob()->then(function($blob) use(&$actualBlob) {
			$actualBlob = $blob;
		});

		self::assertInstanceOf(Blob::class, $actualBlob);
		self::assertSame(32, $actualBlob->size);
	}

	public function testAwaitBlob():void {
		$blobString = random_bytes(32);

		$stream = new Stream();
		$stream->write($blobString);

		$sut = (new Response())->withBody($stream);

		$actualBlob = $sut->awaitBlob();
		self::assertSame($blobString, $actualBlob->getContent());
	}

	public function testText():void {
		$responseText = uniqid("Test!" );

		$stream = new Stream();
		$stream->write($responseText);

		$sut = (new Response())->withBody($stream);

		$actualText = null;
		$sut->text()->then(function($text) use(&$actualText){
			$actualText = $text;
		});

		self::assertSame($responseText, $actualText);
	}

	public function testAwaitText():void {
		$responseText = uniqid("Test! ");

		$stream = new Stream();
		$stream->write($responseText);

		$sut = (new Response())->withBody($stream);

		$actualText = $sut->awaitText();
		self::assertSame($responseText, $actualText);
	}

	public function testFormData():void {
		$data = [
			"name" => "Cody",
			"colour" => "orange",
		];

		$stream = new Stream();
		$stream->write(http_build_query($data));

		$sut = new Response();
		$sut = $sut->withHeader("Content-type", "application/x-www-form-urlencoded");
		$sut = $sut->withBody($stream);

		$actualFormData = null;
		$sut->formData()->then(function($formData) use(&$actualFormData) {
			$actualFormData = $formData;
		});

		self::assertInstanceOf(FormData::class, $actualFormData);
		self::assertSame("Cody", $actualFormData->getString("name"));
		self::assertSame("orange", $actualFormData->getString("colour"));
	}

	public function testAwaitFormData():void {
		$data = [
			"name" => "Cody",
			"colour" => "orange",
		];

		$stream = new Stream();
		$stream->write(http_build_query($data));

		$sut = new Response();
		$sut = $sut->withHeader("Content-type", "application/x-www-form-urlencoded");
		$sut = $sut->withBody($stream);

		$formData = $sut->awaitFormData();
		self::assertInstanceOf(FormData::class, $formData);
		self::assertSame("Cody", $formData->getString("name"));
		self::assertSame("orange", $formData->getString("colour"));
	}

	public function testRedirect_sendsFileLineDebug():void {
		$sut = (new Response());
		$sut->redirect("/somewhere");
		$expectedLine = __LINE__ - 1;
		$expectedFile = __FILE__;
		$expectedFile = str_replace(getcwd(), "", $expectedFile);
		$expectedFile = trim($expectedFile, "/");

		$actualLocation = $sut->getHeaderLine("Location");
		$actualDebugLocation = $sut->getHeaderLine(Response::DEBUG_LOCATION_HEADER);

		self::assertSame("/somewhere", $actualLocation);
		self::assertStringContainsString($expectedFile, $actualDebugLocation);
		self::assertStringEndsWith($expectedLine, $actualDebugLocation);
	}

	public function testReload_sendsFileLineDebug():void {
		$sut = (new Response());
		$sut->reload();
		$expectedLine = __LINE__ - 1;
		$expectedFile = __FILE__;
		$expectedFile = str_replace(getcwd(), "", $expectedFile);
		$expectedFile = trim($expectedFile, "/");

		$actualLocation = $sut->getHeaderLine("Location");
		$actualDebugLocation = $sut->getHeaderLine(Response::DEBUG_LOCATION_HEADER);

		self::assertSame("./", $actualLocation);
		self::assertSame("$expectedFile:$expectedLine", $actualDebugLocation);
	}

	public function testPropGetRedirected_noCurl():void {
		$sut = new Response();
		self::assertFalse($sut->redirected);
	}

	public function testPropGetRedirected_redirect():void {
		$curl = self::createMock(Curl::class);
		$curl->expects(self::exactly(1))
			->method("getInfo")
			->with(CURLINFO_REDIRECT_COUNT)
			->willReturn(5);
		$sut = new Response(200, curl: $curl);
		self::assertTrue($sut->redirected);
	}

	public function testPropGetRedirected_noRedirect():void {
		$curl = self::createMock(Curl::class);
		$curl->expects(self::exactly(1))
			->method("getInfo")
			->with(CURLINFO_REDIRECT_COUNT)
			->willReturn(0);
		$sut = new Response(200, curl: $curl);
		self::assertFalse($sut->redirected);
	}
	public function testStatusPropertiesTrackChangesAndSuccessBoundaries():void {
		$response = new Response();
		self::assertSame(0, $response->status);
		self::assertFalse($response->ok);
		self::assertNull($response->statusText);

		foreach([199 => false, 200 => true, 299 => true, 300 => false, 500 => false] as $code => $ok) {
			$response->setStatus($code);
			self::assertSame($code, $response->status);
			self::assertSame($ok, $response->ok);
		}

		$response->setStatus(StatusCode::NOT_FOUND);
		self::assertSame("Not Found", $response->statusText);
		$response->setStatus(StatusCode::OK);
		self::assertSame("OK", $response->statusText);
	}

	public function testContentTypePropertyTracksHeadersCaseInsensitively():void {
		$headers = new ResponseHeaders();
		$response = new Response(headers: $headers);
		self::assertSame($headers, $response->headers);
		self::assertSame("", $response->type);
		$headers->set("Content-Type", "application/json");
		self::assertSame("application/json", $response->type);
		$headers->set("content-type", "text/plain");
		self::assertSame("text/plain", $response->type);
	}

	public function testUrlPropertiesUseEffectiveCurlUrl():void {
		$curl = self::createMock(Curl::class);
		$curl->expects(self::exactly(2))
			->method("getInfo")
			->with(CURLINFO_EFFECTIVE_URL)
			->willReturn("https://example.com/redirected?key=value");
		$response = new Response(curl: $curl);
		self::assertSame("https://example.com/redirected?key=value", $response->uri);
		self::assertSame("https://example.com/redirected?key=value", $response->url);
	}

	public function testDeferredResponseResolvesWholeBodyAndPreservesReadPosition():void {
		foreach([null, "sha256-" . hash("sha256", "complete body")] as $integrity) {
			$response = new Response();
			$deferred = $response->startDeferredResponse(self::createStub(Curl::class));
			$resolved = null;
			$deferred->getPromise()->then(function(string $body)use(&$resolved) {
				$resolved = $body;
			});
			$response->getBody()->write("complete body");
			$response->getBody()->seek(4);
			self::assertNull($resolved);
			$response->endDeferredResponse($integrity);
			self::assertSame("complete body", $resolved);
			self::assertSame(4, $response->getBody()->tell());
		}
	}

	public function testDeferredIntegrityFailureDoesNotResolveAndCanBeRetried():void {
		$response = new Response();
		$deferred = $response->startDeferredResponse(self::createStub(Curl::class));
		$resolved = null;
		$deferred->getPromise()->then(function(string $body)use(&$resolved) {
			$resolved = $body;
		});
		$response->getBody()->write("original body");
		$response->getBody()->seek(3);

		foreach([
			"unknownalgorithm-hash" => InvalidIntegrityAlgorithmException::class,
			"sha256-" . hash("sha256", "tampered body") => IntegrityMismatchException::class,
		] as $integrity => $exceptionClass) {
			try {
				$response->endDeferredResponse($integrity);
				self::fail("Integrity validation must reject this body");
			}
			catch(HttpException $exception) {
				self::assertInstanceOf($exceptionClass, $exception);
			}
			self::assertNull($resolved);
			self::assertSame(3, $response->getBody()->tell());
		}

		$response->endDeferredResponse("sha256-" . hash("sha256", "original body"));
		self::assertSame("original body", $resolved);
	}
}
