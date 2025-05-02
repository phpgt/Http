<?php
namespace Gt\Http;

use RuntimeException;
use Stringable;

/**
 * @property-read int $size
 * @property-read string $type
 */
class Blob implements Stringable {
	public string $name;
	private string $type;
	protected string $content;

	/**
	 * @param iterable<string|ArrayBuffer|Blob> $blobParts
	 * @param array<string, string> $options
	 */
	public function __construct(
		iterable $blobParts,
		array $options = [],
	) {
		$this->name = "blob";
		$this->type = $options["type"] ?? "";
		$this->content = $this->loadIterable($blobParts);
	}

	public function __toString():string {
		return $this->getContent();
	}

	public function __get(string $name):mixed {
		switch($name) {
		case "size":
			return strlen($this->content);

		case "type":
			return $this->type;
		}

		throw new RuntimeException("Undefined property: $name");
	}

	public function getContent():string {
		return $this->content;
	}

	/** @param iterable<string|ArrayBuffer|Blob> $input */
	protected function loadIterable(iterable $input):string {
		$buffer = "";

		foreach($input as $i) {
			$i = str_replace(
				["\n", "\r\n"],
				PHP_EOL,
				$i
			);

			$buffer .= $i;
		}

		return $buffer;
	}
}
