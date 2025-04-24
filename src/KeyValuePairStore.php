<?php
namespace Gt\Http;

use Generator;
use SplFileObject;

/** @SuppressWarnings("TooManyPublicMethods") */
abstract class KeyValuePairStore {
	/** @var array<string, string|array<string>> */
	protected array $kvp;
	protected int $iteratorIndex;
	/** @var array<int, array<string|Blob>> */
	protected array $iteratorCache;

	/**
	 * @return string A string, without the question mark. Returns an
	 * empty string if no search parameters have been set.
	 */
	public function __toString():string {
		if(!isset($this->kvp)) {
			return "";
		}

		$string = http_build_query($this->kvp);
		return preg_replace(
			"/%5B(\d)%5D/",
			"[]",
			$string
		);
	}

	public function count():int {
		return count($this->kvp);
	}

	public function rewind():void {
		$this->iteratorIndex = 0;
		$this->iteratorCache = $this->cacheIterator();
	}

	public function valid():bool {
		return isset($this->iteratorCache[$this->iteratorIndex]);
	}

	public function current():string|Blob {
		return $this->iteratorCache[$this->iteratorIndex][1];
	}

	public function key():string {
		/** @var string $key */
		$key = $this->iteratorCache[$this->iteratorIndex][0];
		return $key;
	}

	public function next():void {
		$this->iteratorIndex++;
	}

	/**
	 * The delete() method of the URLSearchParams interface deletes the
	 * given search parameter and all its associated values, from the list
	 * of all search parameters.
	 *
	 * @param string $name The name of the parameter to be deleted.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/delete
	 */
	public function delete(string $name):void {
		$name = rtrim($name, "[]");
		if(!isset($this->kvp[$name])) {
			return;
		}

		unset($this->kvp[$name]);
	}

	/**
	 * The entries() method of the URLSearchParams interface returns an
	 * iterator allowing iteration through all key/value pairs contained
	 * in this object. The iterator returns key/value pairs in the same
	 * order as they appear in the query string. The key and value of each
	 * pair are string objects.
	 *
	 * @return Generator<string, string|Blob>
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/entries
	 */
	public function entries():Generator {
		if(!isset($this->kvp)) {
			return;
		}
		foreach($this->kvp as $key => $value) {
			if(is_array($value)) {
				foreach($value as $subValue) {
					yield "{$key}[]" => $subValue;
				}
			}
			else {
				yield $key => $value;
			}
		}
	}

	/**
	 * The forEach() method of the URLSearchParams interface allows
	 * iteration through all values contained in this object via a
	 * callback function.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/forEach
	 */
	public function forEach(callable $callback):void {
		foreach($this->kvp as $key => $value) {
			if(is_array($value)) {
				foreach($value as $subValue) {
					call_user_func(
						$callback,
						"{$key}[]",
						$subValue
					);
				}
			}
			else {
				call_user_func($callback, $key, $value);
			}
		}
	}

	/**
	 * The get() method of the URLSearchParams interface returns the first
	 * value associated to the given search parameter.
	 *
	 * Note: This class implements type-safe getters, getInt, getBool, etc.
	 *
	 * @param string $name The name of the parameter to return.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/get
	 */
	public function get(string $name):mixed {
		$name = rtrim($name, "[]");
		$value = $this->kvp[$name] ?? null;
		if(!$value) {
			return null;
		}

		if(is_array($value)) {
			return $value[0];
		}

		return $value;
	}

	/**
	 * The getAll() method of the URLSearchParams interface returns all
	 * the values associated with a given search parameter as an array.
	 *
	 * @param string $name The name of the parameter to return.
	 * @return array<string>
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/getAll
	 */
	public function getAll(string $name):array {
		$name = rtrim($name, "[]");
		$value = $this->kvp[$name] ?? [];
		if(!is_array($value)) {
			$value = [$value];
		}

		return $value;
	}

	/**
	 * The has() method of the URLSearchParams interface returns a boolean
	 * value that indicates whether a parameter with the specified name
	 * exists.
	 *
	 * @param string $name The name of the parameter to find.
	 * @return bool
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/has
	 */
	public function has(string $name):bool {
		$name = rtrim($name, "[]");
		return isset($this->kvp[$name]);
	}

	/**
	 * The keys() method of the URLSearchParams interface returns an
	 * iterator allowing iteration through all keys contained in this
	 * object. The keys are string objects.
	 *
	 * @return array<string>
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/keys
	 */
	public function keys():array {
		$keys = [];
		foreach($this->kvp as $key => $value) {
			if(is_array($value)) {
				array_push($keys, "{$key}[]");
			}
			else {
				array_push($keys, $key);
			}
		}

		return $keys;
	}

	/**
	 * The URLSearchParams.sort() method sorts all key/value pairs
	 * contained in this object in place and returns undefined. The sort
	 * order is according to unicode code points of the keys. This method
	 * uses a stable sorting algorithm (i.e. the relative order between
	 * key/value pairs with equal keys will be preserved).
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/sort
	 */
	public function sort():void {
		ksort($this->kvp);
	}

	/**
	 * The values() method of the URLsearchParams interface returns an
	 * iterator allowing iteration through all values contained in this
	 * object. The values are string objects.
	 *
	 * @return array<string>
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/values
	 */
	public function values():array {
		$values = [];
		foreach($this->kvp as $value) {
			if(is_array($value)) {
				foreach($value as $subValue) {
					array_push($values, $subValue);
				}
			}
			else {
				array_push($values, $value);
			}
		}
		return $values;
	}

	protected function appendAnyValue(
		string $name,
		mixed $value,
		?string $filename = null
	):void {
		if(is_array($this->kvp[$name] ?? null)) {
			array_push($this->kvp[$name], $value);
		}
		else {
			if(isset($this->kvp[$name])) {
				$this->kvp[$name] = [
					$this->kvp[$name],
					$value,
				];
			}
			else {
				$this->setAnyValue($name, $value, $filename);
			}
		}
	}

	protected function setAnyValue(
		string $name,
		mixed $value,
		?string $filename = null
	):void {
		if(!is_null($filename)) {
			if($value instanceof Blob) {
				$value->name = $filename;
			}
		}

		$this->kvp[$name] = $value;
	}

	/** @return array<int, array<string|Blob>> */
	private function cacheIterator():array {
		$cache = [];
		foreach($this->entries() as $key => $value) {
			array_push($cache, [$key, $value]);
		}
		return $cache;
	}
}
