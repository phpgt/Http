<?php
namespace Gt\Http\Test\Header;

use Gt\Http\Header\HeaderLine;
use Gt\Http\Header\Headers;
use Gt\TypeSafeGetter\TypeSafeGetter;
use PHPUnit\Framework\TestCase;

class HeadersTest extends TestCase {
	const HEADER_ARRAY = [
		"Date" => "Thu, 1 Jan 1970 00:00:00 UTC",
		"Etag" => "0000aaaabbbbcccceeeeffff",
		"Content-Type" => "text/plain; charset=UTF-8",
	];

	public function testAsArray() {
		$headers = new Headers(self::HEADER_ARRAY);
		self::assertEquals(
			self::HEADER_ARRAY,
			$headers->asArray()
		);
	}

	public function testFromArray() {
		$headers = new Headers(self::HEADER_ARRAY);
		$newHeaders = [
			"X-New" => "Example new header",
			"X-Expected" => "These headers should also be found",
		];

		$headers->fromArray($newHeaders);

		$combined = array_merge(self::HEADER_ARRAY, $newHeaders);
		self::assertEquals($combined, $headers->asArray());
	}

	public function testContains() {
		$headers = new Headers(self::HEADER_ARRAY);
		self::assertTrue($headers->contains("Etag"));
		self::assertFalse($headers->contains("Ftag"));
		self::assertTrue($headers->contains("content-type"));
	}

	public function testAdd() {
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->add("Accept", "application/json");
		$headerArray = $headers->asArray();
		self::assertEquals("application/json", $headerArray["Accept"]);
	}

	public function testAddMultiple() {
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->add("Accept", "application/json", "application/xml");
		$headerArray = $headers->asArray();
		self::assertEquals("application/json,application/xml", $headerArray["Accept"]);

		$headers->add("Accept", "text/plain");
		$headerArray = $headers->asArray();
		self::assertEquals("application/json,application/xml,text/plain", $headerArray["Accept"]);
	}

	public function testAddMultipleCommaHeader() {
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->add(
			"Set-Cookie",
			"language=en; expires=Thu, 1-Jan-1970 00:00:00 UTC; path=/; domain=example.com",
			"id=123; expires=Thu, 1-Jan-1970 00:00:00 UTC; path=/; domain=example.com httponly"
		);
		$headerArray = $headers->asArray();
		$cookie = explode("\n", $headerArray["Set-Cookie"]);
		self::assertContains("language=en; expires=Thu, 1-Jan-1970 00:00:00 UTC; path=/; domain=example.com", $cookie);
		self::assertContains("id=123; expires=Thu, 1-Jan-1970 00:00:00 UTC; path=/; domain=example.com httponly", $cookie);
	}

	public function testSet() {
		$now = date("D, j M Y H:i:s T");
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->set("Date", $now);
		$headerArray = $headers->asArray();

		self::assertEquals($now, $headerArray["Date"]);
	}

	public function testRemove() {
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->remove("Date");
		$headerArray = $headers->asArray();
		self::assertArrayNotHasKey("Date", $headerArray);
	}

	public function testGetNotExist() {
		$headers = new Headers(self::HEADER_ARRAY);
		$h = $headers->get("X-NOT-EXISTS");
		self::assertNull($h);
	}

	public function testGet() {
		$headers = new Headers(self::HEADER_ARRAY);
		self::assertEquals(self::HEADER_ARRAY["Date"], $headers->get("date"));
		self::assertEquals(self::HEADER_ARRAY["Content-Type"], $headers->get("content-type"));
	}

	public function testTypeSafeGetterInterface():void {
		$headers = new Headers(self::HEADER_ARRAY);
		self::assertInstanceOf(TypeSafeGetter::class, $headers);
		self::assertSame(self::HEADER_ARRAY["Date"], $headers->getString("date"));
	}

	public function testGetMultiple() {
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->add("Accept", "application/json", "application/xml");
		self::assertEquals("application/json,application/xml", $headers->get("Accept"));
	}

	public function testGetMultipleCommas() {
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->add(
			"Set-Cookie",
			"language=en; expires=Thu, 1-Jan-1970 00:00:00 UTC; path=/; domain=example.com",
			"id=123; expires=Thu, 1-Jan-1970 00:00:00 UTC; path=/; domain=example.com httponly"
		);
		self::assertEquals(
			"language=en; expires=Thu, 1-Jan-1970 00:00:00 UTC; path=/; domain=example.com"
			. "\n"
			. "id=123; expires=Thu, 1-Jan-1970 00:00:00 UTC; path=/; domain=example.com httponly",
			$headers->get("Set-Cookie")
		);
	}

	public function testAddMultipleSetCookieWithoutCommaPreservesSeparateLines():void {
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->add("Set-Cookie", "language=en; Path=/");
		$headers->add("Set-Cookie", "id=123; HttpOnly");

		self::assertCount(5, $headers);
		self::assertSame(
			"language=en; Path=/\nid=123; HttpOnly",
			$headers->asArray()["Set-Cookie"]
		);
	}

	public function testGetAllAggregatesValuesAcrossSeparateHeaderLines():void {
		$firstValue = "Digest realm=\"example.com\", qop=\"auth\"";
		$secondValue = "Digest realm=\"api.example.com\", qop=\"auth-int\"";
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->add("WWW-Authenticate", $firstValue);
		$headers->add("WWW-Authenticate", $secondValue);

		self::assertSame(
			[$firstValue, $secondValue],
			$headers->getAll("WWW-Authenticate")
		);
	}

	public function testAsArrayPreservesAllDuplicateSpecialCaseHeaderLines():void {
		$firstValue = "Digest realm=\"example.com\", qop=\"auth\"";
		$secondValue = "Digest realm=\"api.example.com\", qop=\"auth-int\"";
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->add("WWW-Authenticate", $firstValue);
		$headers->add("WWW-Authenticate", $secondValue);

		self::assertSame(
			$firstValue . "\n" . $secondValue,
			$headers->asArray()["WWW-Authenticate"]
		);
	}

	public function testAsArrayNestedPreservesAllDuplicateHeaderLineValues():void {
		$firstValue = "Digest realm=\"example.com\", qop=\"auth\"";
		$secondValue = "Digest realm=\"api.example.com\", qop=\"auth-int\"";
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->add("WWW-Authenticate", $firstValue);
		$headers->add("WWW-Authenticate", $secondValue);

		self::assertSame(
			[$firstValue, $secondValue],
			$headers->asArray(true)["WWW-Authenticate"]
		);
	}

	public function testGetAllNotExist() {
		$headers = new Headers(self::HEADER_ARRAY);
		$all = $headers->getAll("X-not-exist");
		self::assertEmpty($all);
	}

	public function testGetAll() {
		$headerValues = ["application/json", "application/xml"];
		$headers = new Headers(self::HEADER_ARRAY);
		$headers->add("Accept", ...$headerValues);
		$all = $headers->getAll("aCcEpT");

		foreach($all as $i => $value) {
			self::assertEquals($headerValues[$i], $value);
		}
	}

	public function testIterator() {
		$headers = new Headers(self::HEADER_ARRAY);
		$kvp = [];
		foreach($headers as $i => $headerLine) {
			$kvp[$headerLine->getName()] = $headerLine;
		}

		self::assertCount(count(self::HEADER_ARRAY), $kvp);

		foreach(self::HEADER_ARRAY as $expectedKey => $expectedValue) {
			self::assertArrayHasKey($expectedKey, $kvp);
			self::assertEquals($expectedValue, $kvp[$expectedKey]);
		}
	}

	public function testCaseInsensitive() {
		$headers = new Headers(self::HEADER_ARRAY);
		self::assertTrue($headers->contains("date"));
		self::assertTrue($headers->contains("Date"));
		self::assertTrue($headers->contains("DATE"));

		self::assertEquals(
			self::HEADER_ARRAY["Date"],
			$headers->get("dAtE")
		);

		self::assertTrue($headers->contains("ConTent-Type"));
	}
}
