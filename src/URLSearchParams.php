<?php
namespace GT\Http;

use Countable;
use Generator;
use Gt\PropFunc\MagicProp;
use Gt\TypeSafeGetter\NullableTypeSafeGetter;
use Iterator;
use Stringable;

/**
 * @property-read int $size The read-only URLSearchParams.size property
 * indicates the total number of search parameter entries.
 * @implements Iterator<string, string>
 * @see https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams
 */
class URLSearchParams extends KeyValuePairStore implements Stringable, Countable, Iterator {
	use MagicProp;
	use NullableTypeSafeGetter;

	/**
	 * @param string|array<string, string>|FormData $options
	 * Options is one of:
	 * 1) A string, which will be parsed from
	 * application/x-www-form-urlencoded format. A leading '?' character
	 * is ignored.
	 * 2) A literal sequence of name-value string pairs, or any object —
	 * such as a FormData object — with an iterator that produces a
	 * sequence of string pairs. Note that File entries will be
	 * serialized as [object File] rather than as their filename (as they
	 * would in an application/x-www-form-urlencoded form).
	 * 3) A record of string keys and string values. Note that nesting is
	 * not supported.
	 */
	public function __construct(
		string|array|FormData $options = ""
	) {
		if(is_string($options)) {
			$options = trim($options, "?");
			parse_str($options, $query);
			/** @var array<string, string> $query */
			$this->kvp = $query;
		}
		elseif($options instanceof FormData) {
			foreach($options as $key => $value) {
				$this->append($key, (string)$value);
			}
		}
		else {
			$this->kvp = $options;
		}

		$this->rewind();
	}

	public function __prop_get_size():int {
		return count($this);
	}

	public function append(string $name, string $value):void {
		$this->appendAnyValue($name, $value);
	}

	public function set(string $name, string $value):void {
		$this->setAnyValue($name, $value);
	}
}
