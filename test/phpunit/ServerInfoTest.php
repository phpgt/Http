<?php
namespace GT\Http\Test;

use GT\Http\ServerInfo;
use PHPUnit\Framework\TestCase;

class ServerInfoTest extends TestCase {
	public function testGetHttpHeadersArrayEmpty() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertEmpty($sut->getHttpHeadersArray());
	}

	public function testGetHttpHeadersArray() {
		$httpHeaders = [
			"HTTP_ACCEPT" => "test/php",
			"HTTP_HOST" => "localhost",
			"HTTP_USER_AGENT" => "PHPUnit",
		];
		$server = array_merge(
			self::getServerArray(),
			$httpHeaders
		);
		$sut = new ServerInfo($server);
		$httpHeadersArray = $sut->getHttpHeadersArray();
		self::assertCount(
			count($httpHeaders),
			$httpHeadersArray
		);

		foreach($httpHeaders as $key => $value) {
			$httplessKey = substr($key, strlen("HTTP_"));
			$httplessKey = str_replace("_", "-", $httplessKey);
			self::assertArrayHasKey($httplessKey, $httpHeadersArray);
			self::assertEquals($value, $httpHeadersArray[$httplessKey]);
		}
	}

	public function testGetServerProtocol() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertEquals("TEST/123", $sut->getServerProtocol());
	}

	public function testGetServerProtocolVersion() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertEquals(123, $sut->getServerProtocolVersion());
	}

	public function testGetRequestMethod() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertEquals("TEST", $sut->getRequestMethod());
	}

	public function testGetRequestTime() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertEquals((int)microtime(true), (int)$sut->getRequestTime());
	}

	public function testGetQueryString() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertEquals("key1=value1&key2=value2", $sut->getQueryString());
	}

	public function testWithQueryString() {
		$server = new ServerInfo(self::getServerArray());
		$sut = $server->withQueryString("key3=value3");
		self::assertEquals("key1=value1&key2=value2", $server->getQueryString());
		self::assertEquals("key3=value3", $sut->getQueryString());
	}

	public function testGetQueryParams() {
		$sut = new ServerInfo(self::getServerArray());
		$expected = [
			"key1" => "value1",
			"key2" => "value2",
		];
		self::assertEquals($expected, $sut->getQueryParams());
	}

	public function testWithQueryParams() {
		$server = new ServerInfo(self::getServerArray());
		$origParams = $server->getQueryParams();
		$newParams = [
			"key3" => "value3",
			"key4" => "value4",
		];
		$sut = $server->withQueryParams($newParams);
		self::assertEquals($origParams, $server->getQueryParams());
		self::assertEquals($newParams, $sut->getQueryParams());
	}

	public function testIsHttps() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertFalse($sut->isHttps());
		$sut = new ServerInfo(["HTTPS" => true]);
		self::assertTrue($sut->isHttps());
	}

	public function testGetDocumentRoot() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertEquals(__DIR__, $sut->getDocumentRoot());
	}

	public function testGetRemoteAddress() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertEquals("127.0.0.1", $sut->getRemoteAddress());
	}

	public function testGetScriptFilename() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertEquals(__FILE__, $sut->getScriptFilename());
	}

	public function testGetScriptName() {
		$sut = new ServerInfo(self::getServerArray());
		self::assertEquals(__FILE__, $sut->getScriptName());
	}

	public function testGetRequestUri() {
		$sut = new ServerInfo(self::getServerArray());

		self::assertEquals(
			"http://localhost/example?key1=value1&key2=value2",
			$sut->getRequestUri()
		);
	}

	public function testNullFields() {
		$sut = new ServerInfo([]);
		self::assertNull($sut->getPhpSelf());
		self::assertNull($sut->getGatewayInterface());
		self::assertNull($sut->getServerAddress());
		self::assertNull($sut->getServerName());
		self::assertNull($sut->getServerSoftware());
		self::assertNull($sut->getRemoteHost());
		self::assertNull($sut->getRemotePort());
		self::assertNull($sut->getRemoteUser());
		self::assertNull($sut->getRedirectRemoteUser());
		self::assertNull($sut->getServerAdmin());
		self::assertNull($sut->getServerPort());
		self::assertNull($sut->getServerSignature());
		self::assertNull($sut->getAuthDigest());
		self::assertNull($sut->getAuthUser());
		self::assertNull($sut->getAuthPassword());
		self::assertNull($sut->getAuthType());
		self::assertNull($sut->getRequestScheme());
		self::assertEmpty($sut->getParams());
	}

	public function testGetFullUri8080() {
		$sut = new ServerInfo([
			"HTTP_HOST" => "localhost:8080",
			"HTTPS" => 1,
			"REQUEST_URI" => "/example?key1=value1&key2=value2",
			"SERVER_PORT" => 8080,
			"REMOTE_PORT" => 12345,
			"QUERY_STRING" => "key1=value1&key2=value2",
		]);
		self::assertEquals(
			"https://localhost:8080/example?key1=value1&key2=value2",
			$sut->getFullUri()
		);
	}

	public function testGetFullUri80() {
		$sut = new ServerInfo([
			"HTTP_HOST" => "localhost",
			"REQUEST_URI" => "/example?key1=value1&key2=value2",
			"SERVER_PORT" => 80,
			"REMOTE_PORT" => 12345,
			"QUERY_STRING" => "key1=value1&key2=value2",
		]);
		self::assertEquals(
			"http://localhost/example?key1=value1&key2=value2",
			$sut->getFullUri()
		);
	}

	public function testGetFullUriIncorrectHttpHostHeader() {
		$sut = new ServerInfo([
			"HTTP_HOST" => "localhost",
			"REQUEST_URI" => "/example?key1=value1&key2=value2",
			"SERVER_PORT" => 8080,
			"REMOTE_PORT" => 12345,
			"QUERY_STRING" => "key1=value1&key2=value2",
		]);
		self::assertEquals(
			"http://localhost:8080/example?key1=value1&key2=value2",
			$sut->getFullUri()
		);
	}

	public function testGetServerHost8080MissingFromHostHeader() {
		$sut = new ServerInfo([
			"HTTP_HOST" => "localhost",
			"REQUEST_URI" => "/example?key1=value1&key2=value2",
			"SERVER_PORT" => 8080,
			"REMOTE_PORT" => 12345,
			"QUERY_STRING" => "key1=value1&key2=value2",
		]);
		self::assertEquals(
			"localhost",
			$sut->getServerHost()
		);
	}

	public function testGetServerHost8080() {
		$sut = new ServerInfo([
			"HTTP_HOST" => "localhost:8080",
			"REQUEST_URI" => "/example?key1=value1&key2=value2",
			"SERVER_PORT" => 8080,
			"REMOTE_PORT" => 12345,
			"QUERY_STRING" => "key1=value1&key2=value2",
		]);
		self::assertEquals(
			"localhost",
			$sut->getServerHost()
		);
	}

	public function testGetServerHost80() {
		$sut = new ServerInfo([
			"HTTP_HOST" => "localhost",
			"REQUEST_URI" => "/example?key1=value1&key2=value2",
			"SERVER_PORT" => 80,
			"REMOTE_PORT" => 12345,
			"QUERY_STRING" => "key1=value1&key2=value2",
		]);
		self::assertEquals(
			"localhost",
			$sut->getServerHost()
		);
	}

	protected function getServerArray():array {
		return [
			"PHP_SELF" => __FILE__,
			"SERVER_ADDR" => "127.0.0.1",
			"REMOTE_ADDR" => "127.0.0.1",
			"SERVER_SOFTWARE" => "PHP.Gt",
			"SERVER_PROTOCOL" => "TEST/123",
			"REQUEST_METHOD" => "TEST",
			"REQUEST_TIME_FLOAT" => microtime(true),
			"QUERY_STRING" => "key1=value1&key2=value2",
			"DOCUMENT_ROOT" => __DIR__,
			"SCRIPT_FILENAME" => __FILE__,
			"SCRIPT_NAME" => __FILE__,
			"REQUEST_URI" => "/example?key1=value1&key2=value2",
		];
	}
}