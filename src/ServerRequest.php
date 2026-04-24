<?php
namespace GT\Http;

use GT\Http\Header\RequestHeaders;
use GT\Input\InputData\Datum\FileUpload;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest extends Request implements ServerRequestInterface {
	/** @var array<string, string> */
	protected array $serverData;
	/** @var array<string, mixed> */
	protected array $attributes;
	/** @var array<string, array<string, int|string>> */
	protected array $files;
	/** @var array<string, string> */
	protected array $get;
	/** @var array<string, string> */
	protected array $post;

	/**
	 * @param array<string, string> $serverData From the _SERVER superglobal
	 * @param array<string, array<string, int|string>> $files From the
	 * $_FILES superglobal
	 * @param array<string, string> $get From the _GET superglobal
	 * @param array<string, string> $post From the _POST superglobal
	 */
	public function __construct(
		string $method,
		UriInterface $uri,
		RequestHeaders $headers,
		array $serverData,
		array $files = [],
		array $get = [],
		array $post = []
	) {
		$this->serverData = $serverData;
		$this->attributes = [];
		$this->files = $files;
		$this->get = $get;
		$this->post = $post;
		parent::__construct($method, $uri, $headers);
	}

	/**
	 * @inheritDoc
	 *
	 * @return array<string, string>
	 */
	public function getServerParams():array {
		return $this->serverData;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array<string, string>
	 */
	public function getCookieParams():array {
		parse_str(
			strtr(
				$this->serverData["HTTP_COOKIE"] ?? "",
				["&" => "%26", "+" => "%2B", ";" => "&"]
			),
			$cookieParams
		);
		/** @var array<string, string> $cookieParams */
		return $cookieParams;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array<string, string> $cookies Array of key/value pairs
	 * representing cookies.
	 */
	public function withCookieParams(array $cookies):self {
		$clone = clone $this;
		$clone->serverData["HTTP_COOKIE"] = http_build_query(
			$cookies,
			'',
			'; ',
			PHP_QUERY_RFC3986
		);
		return $clone;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array<string, string|array<string>>
	 */
	public function getQueryParams():array {
		parse_str($this->serverData["QUERY_STRING"] ?? "", $params);
		/** @var array<string, string|array<string>> $params */
		return $params;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array<string, string> $query Array of query string arguments,
	 * typically from $_GET.
	 */
	public function withQueryParams(array $query):self {
		$clone = clone $this;
		$clone->serverData["QUERY_STRING"] = http_build_query($query);
		return $clone;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array<string, UploadedFileInterface> An array tree of
	 * UploadedFileInterface instances; an empty array MUST be returned
	 * if no data is present.
	 */
	public function getUploadedFiles():array {
		$uploadFileArray = [];

		foreach($this->files as $name => $data) {
			$uploadFileArray[$name] = new FileUpload(
				(string)$data["name"],
				(string)($data["type"] ?? ""),
				(int)($data["size"] ?? 0),
				(string)($data["tmp_name"] ?? ""),
			);
		}

		return $uploadFileArray;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array<string, FileUpload> $uploadedFiles An array tree of
	 * UploadedFileInterface instances.
	 * @throws InvalidArgumentException if an invalid structure is provided.
	 */
	public function withUploadedFiles(array $uploadedFiles):self {
		$clone = clone $this;
		$clone->files = [];
		foreach($uploadedFiles as $name => $uploadedFile) {
			$clone->files[$name] = [
				"name" => $uploadedFile->getOriginalName(),
				"type" => $uploadedFile->getMimeType(),
				"size" => $uploadedFile->getSize(),
				"tmp_name" => $uploadedFile->getRealPath(),
			];
		}

		return $clone;
	}

	/**
	 * @inheritDoc
	 *
	 * @return null|array<string, string>|object The deserialized body
	 * parameters, if any. These will typically be an array or object.
	 */
	public function getParsedBody():array|object|null {
		return $this->post;
	}

	/**
	 * @inheritDoc
	 *
	 * @param null|array<string, string>|object $data The deserialized body
	 * data. This will typically be in an array or object.
	 */
	public function withParsedBody($data):self {
		$clone = clone $this;
		$clone->post = (array)$data;
		return $clone;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array<string, mixed> Attributes derived from the request.
	 */
	public function getAttributes():array {
		return $this->attributes;
	}

	/** @inheritDoc */
	public function getAttribute(string $name, $default = null):?string {
		return $this->attributes[$name] ?? $default;
	}

	/** @inheritDoc */
	public function withAttribute(string $name, mixed $value):self {
		$clone = clone $this;
		$clone->attributes[$name] = $value;
		return $clone;
	}

	/** @inheritDoc */
	public function withoutAttribute(string $name):self {
		$clone = clone $this;
		unset($clone->attributes[$name]);
		return $clone;
	}
}
