<?php
namespace GT\Http;

use SplFileObject;

class File extends Blob {
	/**
	 * @param array<ArrayBuffer|Blob|string> $bits
	 * @param string $name
	 * @param array<string, string> $options
	 */
	public function __construct(
		SplFileObject|array $bits,
		string $name,
		array $options = [],
	) {
		if($bits instanceof SplFileObject) {
			$file = $bits;
			$bits = [];
			while(!$file->eof()) {
				array_push($bits, $file->fread(1024));
			}
		}

		/** @var array<ArrayBuffer|Blob|string> $bits */

		parent::__construct($bits, $options);
		$this->name = $name;
	}
}
