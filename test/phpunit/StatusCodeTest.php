<?php
namespace GT\Http\Test;

use GT\Http\StatusCode;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatusCodeTest extends TestCase {
	public function testGet_noState():void {
		$sut = new StatusCode();
		self::assertNull($sut->get());
	}

	public function testSet():void {
		$sut = new StatusCode();
		$sut->set(StatusCode::CREATED);
		self::assertSame(StatusCode::CREATED, $sut->get());
	}

	#[DataProvider("validCodeProvider")]
	public function testSet_validRange(int $code):void {
		$sut = new StatusCode();
		$sut->set($code);
		self::assertSame($code, $sut->get());
	}

	public static function validCodeProvider():array {
		return [[100], [599], [299], [471]];
	}

	#[DataProvider("invalidCodeProvider")]
	public function testSet_invalidCodePreservesState(int $code):void {
		$sut = new StatusCode();
		$sut->set(StatusCode::CREATED);

		try {
			$sut->set($code);
			self::fail("Expected an invalid status code to be rejected");
		}
		catch(InvalidArgumentException) {
			self::assertSame(StatusCode::CREATED, $sut->get());
		}
	}

	public static function invalidCodeProvider():array {
		return [[PHP_INT_MIN], [-1], [0], [99], [600], [PHP_INT_MAX]];
	}

	public function testSet_replacesState():void {
		$sut = new StatusCode();
		$sut->set(StatusCode::CREATED);
		$sut->set(StatusCode::NOT_FOUND);
		self::assertSame(StatusCode::NOT_FOUND, $sut->get());
		self::assertSame("Not Found", $sut->getReasonPhrase());
	}

	public function testReset():void {
		$sut = new StatusCode();
		$sut->reset();
		self::assertNull($sut->get());
		$sut->set(StatusCode::CREATED);
		$sut->reset();
		self::assertNull($sut->get());
		self::assertSame("", $sut->getReasonPhrase());
		$sut->set(StatusCode::OK);
		self::assertSame(StatusCode::OK, $sut->get());
	}

	public function testGetReasonPhrase():void {
		$sut = new StatusCode();
		self::assertSame("", $sut->getReasonPhrase());

		foreach(StatusCode::REASON_PHRASE as $code => $phrase) {
			$sut->set($code);
			self::assertSame($phrase, $sut->getReasonPhrase());
		}

		$sut->set(471);
		self::assertSame("", $sut->getReasonPhrase());
	}
}
