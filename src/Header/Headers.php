<?php
namespace Gt\Http\Header;

use Countable;
use Gt\TypeSafeGetter\NullableTypeSafeGetter;
use Gt\TypeSafeGetter\TypeSafeGetter;
use Iterator;

/**
 * @implements Iterator<int, HeaderLine>
 * @SuppressWarnings("TooManyPublicMethods")
 */
class Headers implements Iterator, Countable, TypeSafeGetter {
	use NullableTypeSafeGetter;

	const COMMA_HEADERS = [
// These cookies use commas within the value, so can't be comma separated.
		"cookie-set",
		"www-authenticate",
		"proxy-authenticate"
	];

	/** @var HeaderLine[] */
	protected array $headerLines = [];
	protected int $iteratorIndex;

	/** @param array<string, string> $headerArray Associative array of
	 * headers (key = header name, value = header value).
	 */
	public function __construct(array $headerArray = []) {
		$this->iteratorIndex = 0;
		if(!empty($headerArray)) {
			$this->fromArray($headerArray);
		}
	}

	/**
	 * @return array<string, string|array<int, string>> Associative array
	 * of headers (key = header name, value = header value).
	 */
	public function asArray(bool $nested = false):array {
		$array = [];

		foreach($this->headerLines as $header) {
			$name = $header->getName();

			if($nested) {
				$array[$name] = $header->getValues();
				continue;
			}

			if(in_array(strtolower($name), self::COMMA_HEADERS)) {
				$array[$name] = $header->getValuesNewlineSeparated();
			}
			else {
				$array[$name] = $header->getValuesCommaSeparated();
			}
		}

		return $array;
	}

	/** @param array<string, string|array<int, string>> $headerArray */
	public function fromArray(array $headerArray):void {
		foreach($headerArray as $key => $value) {
			if(!is_array($value)) {
				$value = [$value];
			}

			$this->headerLines []= new HeaderLine($key, ...$value);
		}
	}

	public function contains(string $name):bool {
		foreach($this->headerLines as $line) {
			if($line->isNamed($name)) {
				return true;
			}
		}

		return false;
	}

	public function add(string $name, string...$values):void {
		$isCommaHeader = false;
		if(strstr($values[0], ",")
		&& in_array(strtolower($name), self::COMMA_HEADERS)) {
			$isCommaHeader = true;
		}

		$headerLineToAdd = null;
		foreach($this->headerLines as $headerLine) {
			if(!$headerLine->isNamed($name)) {
				continue;
			}

			$headerLineToAdd = $headerLine;
		}

		if(is_null($headerLineToAdd) || $isCommaHeader) {
			array_push(
				$this->headerLines,
				new HeaderLine($name, ...$values)
			);
		}
		else {
			$headerLineToAdd->addValue(...$values);
		}

	}

	public function set(string $name, string...$value):void {
		$this->remove($name);
		$this->add($name, ...$value);
	}

	public function remove(string $name):void {
		foreach($this->headerLines as $i => $line) {
			if($line->isNamed($name)) {
				unset($this->headerLines[$i]);
			}
		}
	}

	public function get(string $name):?HeaderLine {
		foreach($this->headerLines as $line) {
			if($line->isNamed($name)) {
				return $line;
			}
		}

		return null;
	}

	/** @return null|array<int, string> */
	public function getAll(string $name):?array {
		foreach($this->headerLines as $line) {
			if($line->isNamed($name)) {
				return $line->getValues();
			}
		}

		return null;
	}

	public function getFirst():string {
		return $this->headerLines[0] ?? "";
	}

	public function current():HeaderLine {
		return $this->headerLines[$this->iteratorIndex];
	}

	public function next():void {
		$this->iteratorIndex++;
	}

	public function key():int {
		return $this->iteratorIndex;
	}

	public function valid():bool {
		return isset($this->headerLines[$this->iteratorIndex]);
	}

	public function rewind():void {
		$this->iteratorIndex = 0;
	}

	public function count():int {
		return count($this->headerLines);
	}
}
