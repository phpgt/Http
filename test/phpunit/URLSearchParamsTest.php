<?php
namespace GT\Http\Test;

use GT\Http\FormData;
use GT\Http\URLSearchParams;
use PHPUnit\Framework\TestCase;

class URLSearchParamsTest extends TestCase {
	public function testToString_empty():void {
		$sut = new URLSearchParams();
		self::assertSame("", (string)$sut);
	}

	public function testToString_onlyQuestionMark():void {
		$sut = new URLSearchParams("?");
		self::assertSame("", (string)$sut);
	}

	public function testToString_oneKeyNoQuestionMark():void {
		$sut = new URLSearchParams("test");
		self::assertSame("test=", (string)$sut);
	}

	public function testToString_oneKeyQuestionMark():void {
		$sut = new URLSearchParams("?test");
		self::assertSame("test=", (string)$sut);
	}

	public function testToString_oneKeyValue():void {
		$sut = new URLSearchParams("testKey=testValue");
		self::assertSame("testKey=testValue", (string)$sut);
	}

	public function testToString_oneKeyValueQuestionMark():void {
		$sut = new URLSearchParams("?testKey=testValue");
		self::assertSame("testKey=testValue", (string)$sut);
	}

	public function testToString_multipleKeyValueFromAssocArray():void {
		$kvp = [
			"testKey1" => "value1",
			"testKey2" => "value2",
			"testKey3" => "value3",
		];
		$sut = new URLSearchParams($kvp);
		$expected = http_build_query($kvp);
		self::assertSame($expected, (string)$sut);
	}

	public function testToString_formDataOptionsEmpty():void {
		$formData = self::createMock(FormData::class);
		$sut = new URLSearchParams($formData);
		self::assertSame("", (string)$sut);
	}

	public function testToString_formDataOptions():void {
		$formData = self::createMock(FormData::class);
		$formData->method("valid")
			->willReturnOnConsecutiveCalls(true, true, true, false);
		$formData->method("key")
			->willReturnOnConsecutiveCalls("testKey1", "testKey2", "testKey3");
		$formData->method("current")
			->willReturnOnConsecutiveCalls("value1", "value2", "value3");
		$sut = new URLSearchParams($formData);
		self::assertSame("testKey1=value1&testKey2=value2&testKey3=value3", (string)$sut);
	}

	public function testSize_empty():void {
		$sut = new URLSearchParams();
		self::assertSame(0, $sut->size);
	}

	public function testSize():void {
		$sut = new URLSearchParams(["one" => 123, "two" => 234]);
		self::assertSame(2, $sut->size);
	}

	public function testAppend():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		self::assertSame("name=Cody", (string)$sut);
	}

	public function testAppend_double():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		self::assertSame("name=Cody&food[]=mushroom&food[]=pepper", (string)$sut);
	}

	public function testAppend_triple():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		$sut->append("food", "corn");
		self::assertSame("name=Cody&food[]=mushroom&food[]=pepper&food[]=corn", (string)$sut);
	}

	public function testDelete_notExists():void {
		$sut = new URLSearchParams();
		$sut->delete("nothing");
		self::assertSame("", (string)$sut);
	}
	public function testDelete_single():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		$sut->delete("name");
		self::assertSame("food[]=mushroom&food[]=pepper", (string)$sut);
	}

	public function testDelete_multiple():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		$sut->delete("food");
		self::assertSame("name=Cody", (string)$sut);
	}

	public function testDelete_multipleWithSquareBrackets():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		$sut->delete("food[]");
		self::assertSame("name=Cody", (string)$sut);
	}

	public function testEntries_empty():void {
		$sut = new URLSearchParams();
		self::assertSame([], iterator_to_array($sut->entries()));
	}

	public function testEntries():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");

		$i = 0;
		foreach($sut->entries() as $key => $value) {
			if($i === 0) {
				self::assertSame("name", $key);
				self::assertSame("Cody", $value);
			}
			if($i === 1) {
				self::assertSame("food[]", $key);
				self::assertSame("mushroom", $value);
			}
			if($i === 2) {
				self::assertSame("food[]", $key);
				self::assertSame("pepper", $value);
			}
			$i++;
		}
	}

	public function testForEach_empty():void {
		$sut = new URLSearchParams();
		$calls = [];
		$sut->forEach(function()use(&$calls) {
			array_push($calls, func_get_args());
		});
		self::assertEmpty($calls);
	}

	public function testForEach():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");

		$calls = [];
		$sut->forEach(function()use(&$calls) {
			array_push($calls, func_get_args());
		});
		self::assertCount(3, $calls);
		self::assertSame("name", $calls[0][0]);
		self::assertSame("Cody", $calls[0][1]);

		self::assertSame("food[]", $calls[1][0]);
		self::assertSame("mushroom", $calls[1][1]);
		self::assertSame("food[]", $calls[2][0]);
		self::assertSame("pepper", $calls[2][1]);
	}

	public function testGet_empty():void {
		$sut = new URLSearchParams();
		self::assertNull($sut->get("something"));
	}

	public function testGet():void {
		$sut = new URLSearchParams("name=Cody");
		self::assertSame("Cody", $sut->get("name"));
	}

	public function testGet_multiple():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");

		self::assertSame("mushroom", $sut->get("food"));
	}

	public function testGet_multipleWithSquareBrackets():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");

		self::assertSame("mushroom", $sut->get("food[]"));
	}

	public function testGetAll_empty():void {
		$sut = new URLSearchParams();
		self::assertEmpty($sut->getAll("something"));
	}

	public function testGetAll_single():void {
		$sut = new URLSearchParams("name=Cody");
		self::assertSame(["Cody"], $sut->getAll("name"));
	}

	public function testGetAll():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		self::assertSame(["mushroom", "pepper"], $sut->getAll("food"));
	}

	public function testGetAll_withSquareBrackets():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		self::assertSame(["mushroom", "pepper"], $sut->getAll("food[]"));
	}

	public function testHas():void {
		$sut = new URLSearchParams();
		self::assertFalse($sut->has("name"));
		$sut->append("name", "Cody");
		self::assertTrue($sut->has("name"));
	}

	public function testHas_withSquareBrackets():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		self::assertTrue($sut->has("food[]"));
	}

	public function testKeys_empty():void {
		$sut = new URLSearchParams();
		self::assertEmpty($sut->keys());
	}

	public function testKeys():void {
		$sut = new URLSearchParams("name=Cody&colour=orange");
		self::assertSame(["name", "colour"], $sut->keys());
	}

	public function testKeys_multiple():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		self::assertSame(["name", "food[]"], $sut->keys());
	}

	public function testSet():void {
		$sut = new URLSearchParams();
		$sut->set("name", "Cody");
		self::assertSame("name=Cody", (string)$sut);
	}

	public function testSet_overwrite():void {
		$sut = new URLSearchParams();
		$sut->set("name", "Cody");
		$sut->set("name", "Scarlett");
		self::assertSame("name=Scarlett", (string)$sut);
	}

	public function testSort():void {
		$sut = new URLSearchParams();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		$sut->sort();
		self::assertSame("food[]=mushroom&food[]=pepper&name=Cody", (string)$sut);
	}

	public function testValues_empty():void {
		$sut = new URLSearchParams();
		self::assertEmpty($sut->values());
	}

	public function testValues():void {
		$sut = new URLSearchParams("name=Cody&colour=orange");
		self::assertSame(["Cody", "orange"], $sut->values());
	}

	public function testValues_multiple():void {
		$sut = new URLSearchParams("name=Cody&food[]=mushroom&food[]=pepper");
		self::assertSame(["Cody", "mushroom", "pepper"], $sut->values());
	}

	public function testIterator_empty():void {
		$sut = new URLSearchParams();
		$key = $value = null;
		foreach($sut as $key => $value) {
			self::assertNull($key, "Iterator should not run");
		}
		self::assertNull($key);
		self::assertNull($value);
	}

	public function testIterator():void {
		$sut = new URLSearchParams("name=Cody&colour=orange");
		$iterations = [];
		foreach($sut as $key => $value) {
			array_push($iterations, [$key, $value]);
		}

		self::assertCount(2, $iterations);
		self::assertSame(["name", "Cody"], $iterations[0]);
		self::assertSame(["colour", "orange"], $iterations[1]);
	}

	public function testIterator_multipleKeys():void {
		$sut = new URLSearchParams("name=Cody&food[]=mushrooms&food[]=biscuits");
		$iterations = [];
		foreach($sut as $key => $value) {
			array_push($iterations, [$key, $value]);
		}

		self::assertCount(3, $iterations);
		self::assertSame(["name", "Cody"], $iterations[0]);
		self::assertSame(["food[]", "mushrooms"], $iterations[1]);
		self::assertSame(["food[]", "biscuits"], $iterations[2]);
	}
}
