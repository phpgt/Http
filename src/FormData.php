<?php
namespace Gt\Http;

use DOMElement;
use DOMNodeList;
use DOMXPath;
use Generator;
use Gt\TypeSafeGetter\NullableTypeSafeGetter;
use SplFileObject;
use Stringable;
use Countable;
use Iterator;

/**
 * @method Generator<string|Blob> entries()
 * @method array<string|Blob> getAll(string $name)
 * @method array<string|Blob> values()
 * @implements Iterator<string, string|Blob>
 * @see https://developer.mozilla.org/en-US/docs/Web/API/FormData
 */
class FormData extends KeyValuePairStore implements Stringable, Countable, Iterator {
	use NullableTypeSafeGetter;

	const USER_INPUT_ELEMENTS = [
		"input",
		"textarea",
		"select",
	];

	/**
	 * @param ?DOMElement $form An HTML <form> element — when specified,
	 * the FormData object will be populated with the form's current
	 * keys/values using the name property of each element for the keys and
	 * their submitted value for the values. It will also encode file
	 * input content.
	 * @param ?DOMElement $submitter A submit button that is a member
	 * of the form. If the submitter has a name attribute or is an
	 * <input type="image">, its data will be included in the FormData
	 * object (e.g. btnName=btnValue).
	 */
	public function __construct(
		private readonly ?DOMElement $form = null,
		?DOMElement $submitter = null,
	) {
		if($form) {
			$this->kvp = $this->extractKvpFromForm($form);
		}
		else {
			$this->kvp = [];
		}

		if($submitter) {
			$foundForm = false;
			while($parent = $submitter->parentNode) {
				if($parent === $this->form) {
					$foundForm = true;
					break;
				}
			}

			if(!$foundForm) {
				throw new HttpException(
					"Submitter is not part of the form"
				);
			}

			$this->append(
				$submitter->getAttribute("name"),
				$submitter->getAttribute("value"),
			);
		}

		$this->rewind();
	}

	public function getFile(string $name):?File {
		return $this->getInstance($name, File::class);
	}

	public function getBlob(string $name):?Blob {
		return $this->getInstance($name, Blob::class);
	}

	/**
	 * The append() method of the FormData interface appends a new value
	 * onto an existing key inside a FormData object, or adds the key if
	 * it does not already exist.
	 *
	 * The difference between set() and append() is that if the specified
	 * key already exists, set() will overwrite all existing values with
	 * the new one, whereas append() will append the new value onto the
	 * end of the existing set of values.
	 *
	 * @param string $name The name of the field whose data is contained
	 * in value.
	 * @param string $value The field's value. This can be a string or
	 * Blob (including subclasses such as File). If none of these are
	 * specified the value is converted to a string.
	 * @param ?string $filename The filename reported to the server , when
	 * a Blob or File is passed as the second parameter.
	 * The default filename for Blob objects is "blob". The default
	 * filename for File objects is the file's filename.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/FormData/append
	 */
	public function append(
		string $name,
		Blob|File|SplFileObject|string $value,
		?string $filename = null
	):void {
		$value = $this->normaliseFileValue($value);
		$this->appendAnyValue($name, $value, $filename);
	}

	public function set(
		string $name,
		Blob|File|SplFileObject|string $value,
		?string $filename = null,
	):void {
		$value = $this->normaliseFileValue($value);
		$this->setAnyValue($name, $value, $filename);
	}

	/**
	 * @return array<string, string|array<string>>
	 * @SuppressWarnings("CyclomaticComplexity")
	 */
	private function extractKvpFromForm(DOMElement $form):array {
		$kvp = [];

		$xpath = new DOMXPath($form->ownerDocument);
		$nameElementList = $xpath->query(".//*[@name]", $form);
		if(!$nameElementList || $nameElementList->length === 0) {
			return $kvp;
		}

		/** @var DOMNodeList<DOMElement> $nameElementList */
		for($i = 0, $len = $nameElementList->length; $i < $len; $i++) {
			/** @var DOMElement $item */
			$item = $nameElementList->item($i);
			if(!in_array($item->tagName, self::USER_INPUT_ELEMENTS)) {
				continue;
			}
			$key = $item->getAttribute("name");
			if(str_ends_with($key, "[]")) {
				$key = substr($key, 0, -2);
			}
			$value = "";

			if($item->tagName === "textarea") {
				$value = $item->nodeValue ?? "";
			}
			elseif($item->tagName === "select") {
				$value = $this->getValueFromSelect($item);
			}
			else {
				$value = $item->getAttribute("value");
			}

			if(isset($kvp[$key])) {
				if(is_array($kvp[$key])) {
					array_push($kvp[$key], $value);
				}
				else {
					$kvp[$key] = [$kvp[$key], $value];
				}
			}
			else {
				$kvp[$key] = $value;
			}
		}

		return $kvp;
	}

	private function getValueFromSelect(DOMElement $select):string {
		$optionList = $select->getElementsByTagName("option");
		for($j = 0, $optLen = $optionList->length; $j < $optLen; $j++) {
			/** @var DOMElement $option */
			$option = $optionList->item($j);
			if($option->hasAttribute("selected")) {
				return $option->getAttribute("value")
					?: $option->nodeValue;
			}
		}

		return "";
	}

	private function normaliseFileValue(Blob|File|SplFileObject|string $value):Blob|File|string {
		if(!$value instanceof SplFileObject) {
			return $value;
		}

		/** @var SplFileObject $file */
		$file = $value;
		return new File($file, $file->getFilename());
	}
}
