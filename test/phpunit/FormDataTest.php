<?php
namespace GT\Http\Test;

use DOMDocument;
use DOMElement;
use GT\Http\Blob;
use GT\Http\File;
use GT\Http\FormData;
use GT\Http\HttpException;
use PHPUnit\Framework\TestCase;

class FormDataTest extends TestCase {
	public function testConstruct_submitterNotFoundInForm():void {
		self::expectException(HttpException::class);
		self::expectExceptionMessage("Submitter is not part of the form");
		new FormData(submitter: new DOMElement("button"));
	}

	public function testConstruct_submitterIncluded():void {
		$html = <<<HTML
		<!doctype html>
		<html lang="en">
			<body>
				<form>
					<label>
						<span>Your name</span>
						<input name="your-name" value="Cody" required />
					</label>
					<label>
						<span>Your email</span>
						<input type="email" name="email" value="cody@g105b.com" required />
					</label>
					<button name="do" value="submit">Submit</button>
				</form>
			</body>
		</html>
		HTML;
		$document = new DOMDocument("1.0", "utf-8");
		$document->loadHTML($html);
		$form = $document->getElementsByTagName("form")[0];
		$button = $document->getElementsByTagName("button")[0];
		$sut = new FormData($form, $button);
		self::assertSame("your-name=Cody&email=cody%40g105b.com&do=submit", (string)$sut);
	}

	public function testConstruct_noFormFields():void {
		$html = <<<HTML
		<!doctype html>
		<html lang="en">
			<body>
				<form>
					<label>
						<span>Your name</span>
						<input id="name-field" />
					</label>
					<label>
						<span>Your email</span>
						<input type="email" id="email-field" />
					</label>
					<button>Submit</button>
				</form>
			</body>
		</html>
		HTML;
		$document = new DOMDocument("1.0", "utf-8");
		$document->loadHTML($html);
		$form = $document->getElementsByTagName("form")[0];
		$sut = new FormData($form);
		$key = null;
		foreach($sut as $key => $value) {
// this line should never execute:
			self::assertNull($key);
		}

		self::assertNull($key);
	}

	public function testConstruct_textArea():void {
		$html = <<<HTML
		<!doctype html>
		<html lang="en">
			<body>
				<form>
					<label>
						<span>Your name</span>
						<input name="name" />
					</label>
					<label>
						<span>Your message</span>
						<textarea name="message">Hello, PHP.Gt!</textarea>
					</label>
					<button name="do" value="submit">Submit</button>
				</form>
			</body>
		</html>
		HTML;
		$document = new DOMDocument("1.0", "utf-8");
		$document->loadHTML($html);
		$form = $document->getElementsByTagName("form")[0];
		$sut = new FormData($form);
		self::assertSame("Hello, PHP.Gt!", $sut->getString("message"));
	}

	public function testConstruct_multiple():void {
		$html = <<<HTML
		<!doctype html>
		<html lang="en">
			<body>
				<form>
					<label>
						<span>Address 1</span>
						<input name="address" value="221B Baker Street, London" />
					</label>
					<label>
						<span>Address 2</span>
						<input name="address" value="32 Windsor Gardens, London" />
					</label>
					<label>
						<span>Address 3</span>
						<input name="address" value="30 Wellington Square in Chelsea, London" />
					</label>
				</form>
			</body>
		</html>
		HTML;
		$document = new DOMDocument("1.0", "utf-8");
		$document->loadHTML($html);
		$form = $document->getElementsByTagName("form")[0];
		$sut = new FormData($form);
		self::assertSame("221B Baker Street, London", $sut->getString("address"));
// Should always return the first of the same value.
		self::assertSame("221B Baker Street, London", $sut->getString("address"));
		$allAddresses = $sut->getAll("address");
		self::assertCount(3, $allAddresses);
		self::assertSame("30 Wellington Square in Chelsea, London", $allAddresses[2]);
	}

	public function testConstruct_multipleSquareBrackets():void {
		$html = <<<HTML
		<!doctype html>
		<html lang="en">
			<body>
				<form>
					<label>
						<span>Address 1</span>
						<input name="address[]" value="221B Baker Street, London" />
					</label>
					<label>
						<span>Address 2</span>
						<input name="address[]" value="32 Windsor Gardens, London" />
					</label>
					<label>
						<span>Address 3</span>
						<input name="address[]" value="30 Wellington Square in Chelsea, London" />
					</label>
				</form>
			</body>
		</html>
		HTML;
		$document = new DOMDocument("1.0", "utf-8");
		$document->loadHTML($html);
		$form = $document->getElementsByTagName("form")[0];
		$sut = new FormData($form);
		self::assertSame("221B Baker Street, London", $sut->getString("address"));
// Should always return the first of the same value.
		self::assertSame("221B Baker Street, London", $sut->getString("address"));
		$allAddresses = $sut->getAll("address");
		self::assertCount(3, $allAddresses);
		self::assertSame("30 Wellington Square in Chelsea, London", $allAddresses[2]);
	}

	public function testToString_empty():void {
		$sut = new FormData();
		self::assertSame("", (string)$sut);
	}

	public function testToString_form():void {
		$kvp = [
			"key1" => "value1",
			"key2" => "value2",
		];

		$form = self::mockForm($kvp);
		$sut = new FormData($form);

		self::assertSame("key1=value1&key2=value2", (string)$sut);
	}

	public function testToString_formMultipleValues():void {
		$kvp = [
			"name" => "Cody",
			"food" => ["mushrooms", "biscuits"],
		];

		$form = self::mockForm($kvp);
		$sut = new FormData($form);

		self::assertSame("name=Cody&food[]=mushrooms&food[]=biscuits", (string)$sut);
	}

	public function testToString_buttonsAreNotIncludedByDefault():void {
		$html = <<<HTML
		<!doctype html>
		<html lang="en">
			<body>
				<form>
					<label>
						<span>Your name</span>
						<input name="your-name" value="Cody" required />
					</label>
					<label>
						<span>Your email</span>
						<input type="email" name="email" value="cody@g105b.com" required />
					</label>
					<button name="do" value="submit">Submit</button>
				</form>
			</body>
		</html>
		HTML;
		$form = self::mockForm($html);
		$sut = new FormData($form);
		self::assertSame("your-name=Cody&email=cody%40g105b.com", (string)$sut);
	}

	public function testToString_HtmlWithSelectButNoMatchingOptions():void {
		$html = <<<HTML
		<!doctype html>
		<html lang="en">
			<body>
				<form>
					<label>
						<span>Your name</span>
						<input name="your-name" value="Cody" required />
					</label>
					<label>
						<span>Your email</span>
						<input type="email" name="email" value="cody@g105b.com" required />
					</label>
					<label>
						<span>Your colour</span>
						<select name="colour">
							<option>White</option>
							<option>Black</option>
							<option>Tabby</option>
						</select>
					</label>
					<button name="do" value="submit">Submit</button>
				</form>
			</body>
		</html>
		HTML;
		$form = self::mockForm($html);
		$sut = new FormData($form);
		self::assertSame("your-name=Cody&email=cody%40g105b.com&colour=", (string)$sut);
	}

	public function testToString_HtmlWithSelect():void {
		$html = <<<HTML
		<!doctype html>
		<html lang="en">
			<body>
				<form>
					<label>
						<span>Your name</span>
						<input name="your-name" value="Cody" required />
					</label>
					<label>
						<span>Your email</span>
						<input type="email" name="email" value="cody@g105b.com" required />
					</label>
					<label>
						<span>Your colour</span>
						<select name="colour">
							<option>White</option>
							<option>Black</option>
							<option selected>Orange</option>
							<option>Tabby</option>
						</select>
					</label>
					<button name="do" value="submit">Submit</button>
				</form>
			</body>
		</html>
		HTML;
		$form = self::mockForm($html);
		$sut = new FormData($form);
		self::assertSame("your-name=Cody&email=cody%40g105b.com&colour=Orange", (string)$sut);
	}

	public function testAppend():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		self::assertSame("name=Cody", (string)$sut);
	}

	public function testAppend_double():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		self::assertSame("name=Cody&food[]=mushroom&food[]=pepper", (string)$sut);
	}

	public function testAppend_triple():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		$sut->append("food", "corn");
		self::assertSame("name=Cody&food[]=mushroom&food[]=pepper&food[]=corn", (string)$sut);
	}

	public function testDelete_notExists():void {
		$sut = new FormData();
		$sut->delete("nothing");
		self::assertSame("", (string)$sut);
	}
	public function testDelete_single():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		$sut->delete("name");
		self::assertSame("food[]=mushroom&food[]=pepper", (string)$sut);
	}

	public function testDelete_multiple():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		$sut->delete("food");
		self::assertSame("name=Cody", (string)$sut);
	}

	public function testDelete_multipleWithSquareBrackets():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		$sut->delete("food[]");
		self::assertSame("name=Cody", (string)$sut);
	}

	public function testEntries_empty():void {
		$sut = new FormData();
		self::assertSame([], iterator_to_array($sut->entries()));
	}

	public function testEntries():void {
		$sut = new FormData();
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
		$sut = new FormData();
		$calls = [];
		$sut->forEach(function()use(&$calls) {
			array_push($calls, func_get_args());
		});
		self::assertEmpty($calls);
	}

	public function testForEach():void {
		$sut = new FormData();
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
		$sut = new FormData();
		self::assertNull($sut->get("something"));
	}

	public function testGet():void {
		$form = self::mockForm(["name" => "Cody"]);
		$sut = new FormData($form);
		self::assertSame("Cody", $sut->get("name"));
	}

	public function testGet_multiple():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");

		self::assertSame("mushroom", $sut->get("food"));
	}

	public function testGet_multipleWithSquareBrackets():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");

		self::assertSame("mushroom", $sut->get("food[]"));
	}

	public function testGetAll_empty():void {
		$sut = new FormData();
		self::assertEmpty($sut->getAll("something"));
	}

	public function testGetAll_single():void {
		$form = self::mockForm(["name" => "Cody"]);
		$sut = new FormData($form);
		self::assertSame(["Cody"], $sut->getAll("name"));
	}

	public function testGetAll():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		self::assertSame(["mushroom", "pepper"], $sut->getAll("food"));
	}

	public function testGetAll_withSquareBrackets():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		self::assertSame(["mushroom", "pepper"], $sut->getAll("food[]"));
	}

	public function testHas():void {
		$sut = new FormData();
		self::assertFalse($sut->has("name"));
		$sut->append("name", "Cody");
		self::assertTrue($sut->has("name"));
	}

	public function testHas_withSquareBrackets():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		self::assertTrue($sut->has("food[]"));
	}

	public function testKeys_empty():void {
		$sut = new FormData();
		self::assertEmpty($sut->keys());
	}

	public function testKeys():void {
		$form = self::mockForm([
			"name" => "Cody",
			"colour" => "orange",
		]);
		$sut = new FormData($form);
		self::assertSame(["name", "colour"], $sut->keys());
	}

	public function testKeys_multiple():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		self::assertSame(["name", "food[]"], $sut->keys());
	}

	public function testSet():void {
		$sut = new FormData();
		$sut->set("name", "Cody");
		self::assertSame("name=Cody", (string)$sut);
	}

	public function testSet_overwrite():void {
		$sut = new FormData();
		$sut->set("name", "Cody");
		$sut->set("name", "Scarlett");
		self::assertSame("name=Scarlett", (string)$sut);
	}

	public function testSet_fileFilename():void {
		$sut = new FormData();
		$sut->set("upload", new File([], "picture.jpg"));
		self::assertSame("picture.jpg", $sut->getFile("upload")->name);
		$sut->set("upload", new File([], "picture.jpg"), "updated.jpg");
		self::assertSame("updated.jpg", $sut->getFile("upload")->name);
	}

	public function testSet_blobFilename():void {
		$sut = new FormData();
		$sut->set("upload", new Blob([]));
		self::assertSame("blob", $sut->getBlob("upload")->name);
		$sut->set("upload", new Blob([]), "updated.jpg");
		self::assertSame("updated.jpg", $sut->getBlob("upload")->name);
	}

	public function testSort():void {
		$sut = new FormData();
		$sut->append("name", "Cody");
		$sut->append("food", "mushroom");
		$sut->append("food", "pepper");
		$sut->sort();
		self::assertSame("food[]=mushroom&food[]=pepper&name=Cody", (string)$sut);
	}

	public function testValues_empty():void {
		$sut = new FormData();
		self::assertEmpty($sut->values());
	}

	public function testValues():void {
		$form = self::mockForm([
			"name" => "Cody",
			"colour" => "orange",
		]);
		$sut = new FormData($form);
		self::assertSame(["Cody", "orange"], $sut->values());
	}

	public function testValues_multiple():void {
		$form = self::mockForm([
			"name" => "Cody",
			"food" => ["mushroom", "pepper"],
		]);
		$sut = new FormData($form);
		self::assertSame(["Cody", "mushroom", "pepper"], $sut->values());
	}

	public function testIterator_empty():void {
		$sut = new FormData();
		$key = $value = null;
		foreach($sut as $key => $value) {
			self::assertNull($key, "Iterator should not run");
		}
		self::assertNull($key);
		self::assertNull($value);
	}

	public function testIterator():void {
		$form = self::mockForm([
			"name" => "Cody",
			"colour" => "orange",
		]);
		$sut = new FormData($form);
		$iterations = [];
		foreach($sut as $key => $value) {
			array_push($iterations, [$key, $value]);
		}

		self::assertCount(2, $iterations);
		self::assertSame(["name", "Cody"], $iterations[0]);
		self::assertSame(["colour", "orange"], $iterations[1]);
	}

	public function testIterator_multipleKeys():void {
		$form = self::mockForm([
			"name" => "Cody",
			"food" => ["mushrooms", "biscuits"]
		]);
		$sut = new FormData($form);
		$iterations = [];
		foreach($sut as $key => $value) {
			array_push($iterations, [$key, $value]);
		}

		self::assertCount(3, $iterations);
		self::assertSame(["name", "Cody"], $iterations[0]);
		self::assertSame(["food[]", "mushrooms"], $iterations[1]);
		self::assertSame(["food[]", "biscuits"], $iterations[2]);
	}

	private static function mockForm(array|string $input):DOMElement {
		$document = new DOMDocument("1.0", "utf-8");
		if(is_array($input)) {
			$kvp = $input;
			$document->loadHTML("<!doctype html><html lang=\"en\"><body></body></html>");
			$body = $document->getElementsByTagName("body")[0];
			$form = $document->createElement("form");

			foreach($kvp as $key => $value) {
				if(is_array($value)) {
					foreach($value as $subValue) {
						$input = $document->createElement("input");
						$input->setAttribute("name", $key);
						$input->setAttribute("value", $subValue);
						$form->append($input);
					}
				}
				else {
					$input = $document->createElement("input");
					$input->setAttribute("name", $key);
					$input->setAttribute("value", $value);
					$form->append($input);
				}
			}

			$body->append($form);
		}
		else {
			$document->loadHTML($input);
			$form = $document->getElementsByTagName("form")[0];
		}

		return $form;
	}
}
