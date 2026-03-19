<?php
namespace Gt\Http;

use Gt\Http\Header\Headers;
use Psr\Http\Message\StreamInterface;

trait Message {
	public readonly Headers $headers;
	protected string $protocol;
	protected StreamInterface $stream;

	/** @inheritDoc */
	public function getProtocolVersion():string {
		if(str_contains($this->protocol, "/")) {
			return substr(
				$this->protocol,
				strpos($this->protocol, "/") + 1
			);
		}

		return $this->protocol;
	}

	/** @inheritDoc */
	public function withProtocolVersion(string $version):static {
		if(!is_numeric($version)) {
			throw new InvalidProtocolHttpException($version);
		}

		$clone = clone $this;

		if(isset($clone->protocol) && strstr($clone->protocol, "/")) {
			$versionStartPos = strpos(
					$clone->protocol,
					"/"
				) + 1;
			$versionEndPos = strpos(
				$clone->protocol,
				" "
			) ?: 0;

			$protocol = "";
			$protocol .= substr(
				$clone->protocol,
				0,
				$versionStartPos
			);
			$protocol .= $version;
			$protocol .= substr(
				$clone->protocol,
				$versionEndPos
			);
			$clone->protocol = $protocol;
		}
		else {
			$clone->protocol = $version;
		}

		return $clone;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array<string, array<string>> Returns an associative array
	 * of the message's headers. Each key MUST be a header name, and each
	 * value MUST be an array of strings for that header.
	 */
	public function getHeaders():array {
		/** @var array<string, array<string>> $headers */
		$headers = $this->headers->asArray(true);
		return $headers;
	}

	/** @inheritDoc */
	public function hasHeader(string $name):bool {
		return $this->headers->contains($name);
	}

	/**
	 * @inheritDoc
	 *
	 * @return array<string> An array of string values as provided for the
	 * given header. If the header does not appear in the message, this
	 * method MUST return an empty array.
	 */
	public function getHeader(string $name):array {
		return $this->headers->getAll($name);
	}

	/** @inheritDoc */
	public function getHeaderLine(string $name):string {
		$header = $this->headers->get($name);

		if($header) {
			return $header->getValuesCommaSeparated();
		}
		else {
			return "";
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @param string|string[] $value Header value(s).
	 */
	public function withHeader(string $name, $value):static {
		if(!is_array($value)) {
			$value = [$value];
		}

		$clone = clone $this;
		$clone->headers->set($name, ...$value);
		return $clone;
	}

	/**
	 * @inheritDoc
	 *
	 * @param string|string[] $value Header value(s).
	 */
	public function withAddedHeader(string $name, $value):static {
		if(!is_array($value)) {
			$value = [$value];
		}

		$clone = clone $this;
		$clone->headers->add($name, ...$value);
		return $clone;
	}

	/** @inheritDoc */
	public function withoutHeader(string $name):static {
		$clone = clone $this;
		$clone->headers->remove($name);
		return $clone;
	}

	/** @param array<string, string|array<int, string>> $headers */
	public function setHeaders(array $headers):static {
		$clone = clone $this;
		$clone->headers->fromArray($headers);
		return $clone;
	}

	/** @inheritDoc */
	public function getBody():StreamInterface {
		if(!isset($this->stream)) {
			$this->stream = new Stream("php://memory");
		}
		return $this->stream;
	}

	/** @inheritDoc */
	public function withBody(StreamInterface $body):static {
		$clone = clone $this;
		$clone->stream = $body;
		return $clone;
	}
}
