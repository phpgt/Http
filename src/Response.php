<?php /** @noinspection PhpUnusedPrivateMethodInspection */
namespace Gt\Http;

use Gt\Curl\CurlInterface;
use Gt\Http\Header\ResponseHeaders;
use Gt\Json\JsonObject;
use Gt\Json\JsonObjectBuilder;
use Gt\Promise\Deferred;
use Gt\Promise\Promise;
use Gt\PropFunc\MagicProp;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @property ResponseHeaders $headers
 * @property-read bool $ok
 * @property-read bool $redirected
 * @property-read int $status
 * @property-read string $statusText
 * @property-read string $type
 * @property-read UriInterface $uri
 * @property-read UriInterface $url
 * @SuppressWarnings("UnusedPrivateMethod")
 * @SuppressWarnings("TooManyPublicMethods")
 * @SuppressWarnings("TooManyMethods")
 * @SuppressWarnings("ExcessiveClassComplexity")
 */
class Response implements ResponseInterface {
	use Message;
	use MagicProp;

	const DEBUG_LOCATION_HEADER = "X-Location-Sent-From";

	/** @var null|callable */
	private $exitCallback;
	private Deferred $deferred;
	private CurlInterface $curl;

	public function __construct(
		private ?int $statusCode = null,
		?ResponseHeaders $headers = null,
		private readonly ?Request $request = null,
	) {
		$this->headers = $headers ?? new ResponseHeaders();
		$this->stream = new Stream();
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_headers():ResponseHeaders {
		return $this->getResponseHeaders();
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_ok():bool {
		return ($this->getStatusCode() >= 200
			&& $this->getStatusCode() < 300);
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_redirected():bool {
		if(!isset($this->curl)) {
			return false;
		}

		$redirectCount = $this->curl->getInfo(
			CURLINFO_REDIRECT_COUNT
		);
		return $redirectCount > 0;
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_status():int {
		return $this->getStatusCode();
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_statusText():?string {
		return StatusCode::REASON_PHRASE[$this->status] ?? null;
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_uri():string {
		if(!isset($this->curl)) {
			return $this->request->getUri();
		}
		return $this->curl->getInfo(CURLINFO_EFFECTIVE_URL);
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_url():string {
		return $this->uri;
	}

	/** @phpstan-ignore-next-line */
	private function __prop_get_type():string {
		return $this->headers->get("content-type")?->getValue() ?? "";
	}

	public function setExitCallback(callable $callback):void {
		$this->exitCallback = $callback;
	}

	public function reload():void {
		$this->redirect($this->request?->getUri() ?? new Uri("./"));
	}

	public function reloadWithoutQuery():void {
		$uri = $this->request?->getUri() ?? new Uri();
		$uri = $uri->withQuery("");
		$this->redirect($uri);
	}

	public function redirect(
		string|UriInterface $uri,
		int $statusCode = 303
	):void {
		/**
		 * @var array{array{
		 *         function: string,
		 *         file: string,
		 *         line: int,
		 * }} $backtrace
		 */
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$thisFile = __FILE__;
		$tries = 0;
		do {
			$previousBacktrace = array_shift($backtrace);
			$found = $previousBacktrace["file"] !== $thisFile;
			$tries++;
		}
		while(!$found && $tries <= 3);

		$cwd = getcwd() ?: "";
		$debugLocation = $previousBacktrace["file"];
		$debugLocation = str_replace($cwd, "", $debugLocation);
		$debugLocation = trim($debugLocation, "/");
		$debugLocation .= ":$previousBacktrace[line]";
		$this->statusCode = $statusCode;
		$this->headers->set("Location", (string)$uri);
		$this->headers->set(self::DEBUG_LOCATION_HEADER, $debugLocation);
		if(isset($this->exitCallback)) {
			call_user_func($this->exitCallback);
		}
	}

	/** @inheritDoc */
	public function getStatusCode():int {
		return $this->statusCode ?? 0;
	}

	/** @inheritDoc */
// phpcs:ignore
	public function withStatus(
		int $code,
		string $reasonPhrase = ''
	):self {
		$clone = clone $this;
		$clone->statusCode = $code;
		return $clone;
	}

	/** @inheritDoc */
	public function getReasonPhrase():string {
		return StatusCode::REASON_PHRASE[$this->statusCode];
	}

	public function getResponseHeaders():ResponseHeaders {
		return $this->headers;
	}

	/**
	 * Takes the Response's stream and reads it to completion. Returns a Promise which resolves with the result
	 * as a Gt\Http\ArrayBuffer.
	 *
	 * Note: if no Async loop is set up, the returned Promise will resolve in a blocking way, always being
	 * resolved or rejected. See https://www.php.gt/fetch for a complete async implementation.
	 */
	public function arrayBuffer():Promise {
		$promise = $this->getPromise();
		$promise->then(function(string $responseText) {
			return $this->arrayBufferFromResponseText($responseText);
		});

		return $promise;
	}

	public function awaitArrayBuffer():ArrayBuffer {
		$arrayBuffer = null;
		if($responseText = $this->getBody()->getContents()) {
			$arrayBuffer = $this->arrayBufferFromResponseText($responseText);
		}

		$this->arrayBuffer()->then(function(ArrayBuffer $resolved) use(&$arrayBuffer) {
			$arrayBuffer = $resolved;
		});

		return $arrayBuffer;
	}

	private function arrayBufferFromResponseText(string $responseText):ArrayBuffer {
		$bytes = strlen($responseText);
		$arrayBuffer = new ArrayBuffer($bytes);
		for($i = 0; $i < $bytes; $i++) {
			$arrayBuffer->offsetSet($i, ord($responseText[$i]));
		}

		return $arrayBuffer;
	}

	/**
	 * Takes the Response's stream and reads it to completion. Returns a Promise which resolves with the result
	 * as a Gt\Http\Blob.
	 *
	 * Note: if no Async loop is set up, the returned Promise will resolve in a blocking way, always being
	 * resolved or rejected. See https://www.php.gt/fetch for a complete async implementation.
	 */
	public function blob():Promise {
		$promise = $this->getPromise();
		$promise->then(function(string $responseText) {
			return $this->blobFromResponseText($responseText);
		});

		return $promise;
	}

	public function awaitBlob():Blob {
		$blob = null;
		if($responseText = $this->getBody()->getContents()) {
			$blob = $this->blobFromResponseText($responseText);
		}

		$this->blob()->then(function(Blob $resolved) use(&$blob) {
			$blob = $resolved;
		});

		return $blob;
	}

	private function blobFromResponseText(string $responseText):Blob {
		$blobOptions = [
			"type" => $this->getResponseHeaders()->get("content-type")?->getValues()[0],
		];
		return new Blob([$responseText], $blobOptions);
	}

	/**
	 * Takes the Response's stream and reads it to completion. Returns a Promise which resolves with the result
	 * as a Gt\Http\FormData.
	 *
	 * Note: if no Async loop is set up, the returned Promise will resolve in a blocking way, always being
	 * resolved or rejected. See https://www.php.gt/fetch for a complete async implementation.
	 */
	public function formData():Promise {
		$newDeferred = new Deferred();
		$newPromise = $newDeferred->getPromise();

		$deferredPromise = $this->getPromise();
		$deferredPromise->then(function(string $responseText)
		use($newDeferred) {
			$formData = $this->formDataFromResponseText($responseText);
			$newDeferred->resolve($formData);
		});

		return $newPromise;
	}

	public function awaitFormData():FormData {
		$formData = null;
		if($responseText = $this->getBody()->getContents()) {
			$formData = $this->formDataFromResponseText($responseText);
		}

		$this->blob()->then(function(FormData $resolved) use(&$formData) {
			$formData = $resolved;
		});

		return $formData;
	}

	private function formDataFromResponseText(string $responseText):FormData {
		parse_str($responseText, $bodyData);
		$formData = new FormData();
		foreach($bodyData as $key => $value) {
			if(is_array($value)) {
				$value = implode(",", $value);
			}
			$formData->set((string)$key, (string)$value);
		}
		return $formData;
	}

	/**
	 * Takes the Response's stream and reads it to completion. Returns a Promise which resolves with the result
	 * as a Gt\Json\JsonObject.
	 *
	 * Note: if no Async loop is set up, the returned Promise will resolve in a blocking way, always being
	 * resolved or rejected. See https://www.php.gt/fetch for a complete async implementation.
	 *
	 * @param int<1, max> $depth
	 */
	public function json(int $depth = 512, int $options = 0):Promise {
		$promise = $this->getPromise();
		$promise->then(function(string $responseText)use($depth, $options) {
			return $this->jsonFromResponseText($responseText, $depth, $options);
		});

		return $promise;
	}

	/** @param int<1, max> $depth */
	public function awaitJson(int $depth = 512, int $options = 0):JsonObject {
		$jsonObject = null;
		if($responseText = $this->getBody()->getContents()) {
			$jsonObject = $this->jsonFromResponseText($responseText);
		}

		$this->json($depth, $options)->then(function(JsonObject $resolved) use(&$jsonObject) {
			$jsonObject = $resolved;
		});

		return $jsonObject;
	}

	private function jsonFromResponseText(string $responseText, int $depth = 512, int $options = 0):JsonObject {
		$builder = new JsonObjectBuilder($depth, $options);
		return $builder->fromJsonString($responseText);
	}

	/**
	 * Takes the Response's stream and reads it to completion. Returns a Promise which resolves with the result
	 * as a string.
	 *
	 * Note: if no Async loop is set up, the returned Promise will resolve in a blocking way, always being
	 * resolved or rejected. See https://www.php.gt/fetch for a complete async implementation.
	 */
	public function text():Promise {
		$promise = $this->getPromise();
		$promise->then(function(string $responseText) {
			return $responseText;
		});

		return $promise;
	}

	public function awaitText():string {
		$text = $this->getBody()->getContents();

		$this->text()->then(function(string $resolved) use(&$text) {
			$text = $resolved;
		});

		return $text;
	}

	private function getPromise():Promise {
		if(!isset($this->deferred)) {
			$this->deferred = new Deferred();
			$this->deferred->resolve($this->stream->getContents());
		}

		return $this->deferred->getPromise();
	}

	private function checkIntegrity(?string $integrity, string $contents):void {
		if(is_null($integrity)) {
			return;
		}

		[$algo, $hash] = explode("-", $integrity);

		$availableAlgos = hash_algos();
		if(!in_array($algo, $availableAlgos)) {
			throw new InvalidIntegrityAlgorithmException($algo);
		}

		$hashedContents = hash($algo, $contents);

		if($hashedContents !== $hash) {
			throw new IntegrityMismatchException();
		}
	}
}
