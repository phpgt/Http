<?php
namespace GT\Http\Header;

class HeaderLine {
	/** @var string */
	protected $originalNameCase;
	/** @var string */
	protected $name;
	/** @var string[] */
	protected $values;

	public function __construct(string $name, string...$values) {
		$name = str_replace("_", "-", $name);
		$this->originalNameCase = $name;
		$this->name = strtolower($name);
		$this->values = $values;
	}

	public function __toString():string {
		if(in_array($this->name, Headers::NON_COMBINABLE_HEADERS)) {
			return $this->getValuesNewlineSeparated();
		}
		else {
			return $this->getValuesCommaSeparated();
		}
	}

	public function addValue(string...$values):void {
		foreach($values as $v) {
			array_push($this->values, $v);
		}
	}

	public function getName():string {
		return $this->originalNameCase;
	}

	public function getValue(int $position = 0):?string {
		return $this->values[$position] ?? null;
	}

	/** @return string[] */
	public function getValues():array {
		return $this->values;
	}

	public function getValuesCommaSeparated():string {
		return implode(",", $this->values);
	}

	public function getValuesNewlineSeparated():string {
		return implode("\n", $this->values);
	}

	public function isNamed(string $name):bool {
		return $this->name === strtolower($name);
	}
}
