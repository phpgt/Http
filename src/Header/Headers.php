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

	const NON_COMBINABLE_HEADERS = [
		"set-cookie",
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
			$nameLower = strtolower($name);

			if($nested) {
				$array[$name] ??= [];
				$array[$name] = array_merge($array[$name], $header->getValues());
				continue;
			}

			if(!array_key_exists($name, $array)) {
				$array[$name] = "";
			}

			if(in_array($nameLower, self::NON_COMBINABLE_HEADERS)) {
				if($array[$name] !== "") {
					$array[$name] .= "\n";
				}
				$array[$name] .= $header->getValuesNewlineSeparated();
			}
			else {
				if($array[$name] !== "") {
					$array[$name] .= ",";
				}
				$array[$name] .= $header->getValuesCommaSeparated();
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

			if(in_array(strtolower($key), self::NON_COMBINABLE_HEADERS)) {
				foreach($value as $singleValue) {
					array_push($this->headerLines, new HeaderLine($key, $singleValue));
				}
			}
			else {
				array_push($this->headerLines, new HeaderLine($key, ...$value));
			}
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
		if(in_array(strtolower($name), self::NON_COMBINABLE_HEADERS)) {
			foreach($values as $value) {
				array_push($this->headerLines, new HeaderLine($name, $value));
			}
			return;
		}

		$headerLineToAdd = null;
		foreach($this->headerLines as $headerLine) {
			if(!$headerLine->isNamed($name)) {
				continue;
			}

			$headerLineToAdd = $headerLine;
		}

		if(is_null($headerLineToAdd)) {
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
		$matchingValues = [];
		$headerName = null;

		foreach($this->headerLines as $line) {
			if($line->isNamed($name)) {
				$headerName ??= $line->getName();
				$matchingValues = array_merge($matchingValues, $line->getValues());
			}
		}

		if(!$headerName) {
			return null;
		}

		return new HeaderLine($headerName, ...$matchingValues);
	}

	/** @return array<int, string> */
	public function getAll(string $name):array {
		$allValues = [];

		foreach($this->headerLines as $line) {
			if($line->isNamed($name)) {
				$allValues = array_merge($allValues, $line->getValues());
			}
		}

		return $allValues;
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
