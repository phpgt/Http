<?php
namespace Gt\Http\Header;

class Parser {
	protected string $rawHeaders;

	public function __construct(string $rawHeaders) {
		$this->rawHeaders = $rawHeaders;
	}

	public function getProtocolVersion():string {
		return $this->pregMatchProtocol("version");
	}

	public function getStatusCode():int {
		return (int)$this->pregMatchProtocol("code");
	}

	/** @return array<string, string> Associative array of key value pairs
	 * containing each header name and value.
	 */
	public function getKeyValues():array {
		$keyValues = [];

		foreach(explode("\n", $this->rawHeaders) as $i => $h) {
			if($i === 0) {
				continue;
			}

			$h = trim($h);
			$kvp = explode(":", $h, 2);
			$key = $kvp[0];
			$value = $kvp[1] ?? "";
			$value = trim($value);

			if(array_key_exists($key, $keyValues)) {
				if(strtolower($key) === "set-cookie") {
					$keyValues[$key] .= "\n" . $value;
				}
				else {
					$keyValues[$key] .= ", " . $value;
				}

				continue;
			}

			$keyValues[$key] = $value;
		}

		return $keyValues;
	}

	protected function pregMatchProtocol(string $matchName):string {
		$headerLine = strtok($this->rawHeaders, "\n") ?: "";
		/** @noinspection RegExpRedundantEscape */
		$matched = preg_match(
			"/HTTP\/(?P<version>[0-9\.]+)\s*(?P<code>\d+)?/",
			$headerLine,
			$matches
		);
		if($matched !== 1) {
			return "";
		}

		return $matches[$matchName] ?? "";
	}
}
