<?php
namespace Gt\Http;

use RuntimeException;
use SplFixedArray;
use Stringable;

/**
 * @property-read int $byteLength
 * @extends SplFixedArray<int|string>
 */
class ArrayBuffer extends SplFixedArray implements Stringable {
	public function __toString():string {
		return implode("", iterator_to_array($this));
	}

	public function __get(string $name):mixed {
		switch($name) {
		case "byteLength":
			return count($this);
		}

		throw new RuntimeException("Undefined property: $name");
	}

	/**
	 * @SuppressWarnings("UnusedFormalParameter")
	 * @noinspection PhpUnusedParameterInspection
	 */
	// phpcs:ignore
	public function transfer(
		self $oldBuffer,
		?int $newByteLength = null
	):self {
		return $this;
	}

	/**
	 * @SuppressWarnings("UnusedFormalParameter")
	 * @noinspection PhpUnusedParameterInspection
	 */
	// phpcs:ignore
	public function slice(
		int $begin,
		int $end,
	):self {
		return $this;
	}
}
