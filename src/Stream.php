<?php
namespace Gt\Http;

use Exception;
use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface {
	const READABLE_MODES = [
		"r", "w+", "r+", "x+", "c+", "rb", "w+b", "r+b", "x+b",
		"c+b", "rt", "w+t", "r+t", "x+t", "c+t", "a+",
	];
	const WRITABLE_MODES = [
		"w", "w+", "rw", "r+", "x+", "c+", "wb", "w+b", "r+b",
		"x+b", "c+b", "w+t", "r+t", "x+t", "c+t", "a", "a+",
	];

	/** @var resource|null */
	protected $stream;
	protected bool $isSeekable;
	protected bool $isReadable;
	protected bool $isWritable;
	protected string $uri;

	public function __construct(
		string $path = "php://memory",
		string $mode = "r+"
	) {
		try {
			$stream = fopen($path, $mode);
		}
		catch(Exception) {
			$stream = false;
		}

		if($stream === false) {
			throw new StreamNotOpenableException();
		}
		$this->stream = $stream;


		$streamInfo = stream_get_meta_data($this->stream);
		$this->isSeekable = $streamInfo["seekable"];
		/** @phpstan-ignore-next-line */
		$this->uri = $streamInfo["uri"] ?? "";
		$this->isReadable = in_array($streamInfo["mode"], self::READABLE_MODES);
		$this->isWritable = in_array($streamInfo["mode"], self::WRITABLE_MODES);
	}

	/** @inheritDoc */
	public function __toString():string {
		$this->rewind();
		return $this->getContents();
	}

	/** @inheritDoc */
	public function close():void {
		if(is_resource($this->stream)) {
			fclose($this->stream);
		}
	}

	/** @inheritDoc */
	public function detach() {
		/** @var resource|null $stream */
		$stream = $this->stream;
		unset($this->stream);
		return $stream;
	}

	/** @return resource|null The file handle opened by fopen */
	public function getFileHandle() {
		return $this->stream ?? null;
	}

	/** @inheritDoc */
	public function getSize():?int {
		return null;
	}

	/** @inheritDoc */
	public function tell():int {
		return ftell($this->stream) ?: 0;
	}

	/** @inheritDoc */
	public function eof():bool {
		return feof($this->stream);
	}

	/** @inheritDoc */
	public function isSeekable():bool {
		return $this->isSeekable;
	}

	/** @inheritDoc */
	public function seek(int $offset, int $whence = SEEK_SET):void {
		fseek($this->stream, $offset, $whence);
		// TODO: Throw exception on failure.
	}

	/** @inheritDoc */
	public function rewind():void {
		rewind($this->stream);
		// TODO: Throw exception on failure.
	}

	/** @inheritDoc */
	public function isWritable():bool {
		return $this->isWritable;
	}

	/** @inheritDoc */
	public function write(string $string):int {
		if(!$this->isWritable()) {
			throw new StreamException("Stream is not writable");
		}

		return fwrite($this->stream, $string) ?: 0;
	}

	/** @inheritDoc */
	public function isReadable():bool {
		return $this->isReadable;
	}

	/** @inheritDoc */
	public function read(int $length):string {
		if(!$this->isReadable()) {
			throw new StreamException("Stream is not readable");
		}

		if($length <= 0) {
			throw new StreamException("Stream read length must be positive");
		}

		return fread($this->stream, $length) ?: "";
	}

	/** @inheritDoc */
	public function getContents():string {
		if(!$this->isReadable()) {
			throw new StreamException("Stream is not readable");
		}

		$position = $this->tell();
		$this->rewind();

		$string = stream_get_contents($this->stream);
		$this->seek($position);

		return $string ?: "";
	}

	/** @inheritDoc */
	public function getMetadata(?string $key = null) {
		$data = stream_get_meta_data($this->stream);
		if(is_null($key)) {
			return $data;
		}

		return $data[$key] ?? null;
	}
}
