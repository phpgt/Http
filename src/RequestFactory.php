<?php
namespace GT\Http;

use GT\Http\Header\RequestHeaders;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class RequestFactory {
	/**
	 * A Request object is a PSR-7 compatible object that is created here
	 * from the current global state (as passed in via the appropriate
	 * associative arrays).
	 * @param array<string, string> $server
	 * @param array<string, array<string, string>> $files
	 * @param array<string, string> $get
	 * @param array<string, string> $post
	 *
	 * @link http://www.php-fig.org/psr/psr-7/
	 */
	public function createServerRequestFromGlobalState(
		array $server,
		array $files,
		array $get,
		array $post,
		string $inputPath = "php://input"
	):ServerRequestInterface {
		$method = $server["REQUEST_METHOD"] ?? "";
		$uri = $this->buildUri($server);

		$headers = $this->buildRequestHeaders($server);

		$serverRequest = $this->buildRequest(
			$method,
			$uri,
			$headers,
			$server,
			$files,
			$get,
			$post,
			$inputPath
		);
		return $serverRequest;
	}

	/**
	 * @param array<string, string> $server
	 * @param array<string, array<string, string>> $files
	 * @param array<string, string> $get
	 * @param array<string, string> $post
	 */
	public function buildRequest(
		string $method,
		UriInterface $uri,
		RequestHeaders $headers,
		array $server,
		array $files,
		array $get,
		array $post,
		string $inputPath
	):ServerRequestInterface {
		$request = new ServerRequest(
			$method,
			$uri,
			$headers,
			$server,
			$files,
			$get,
			$post
		);
		$stream = new Stream($inputPath);
		$request = $request->withBody($stream);

		if($protocolString = $server["SERVER_PROTOCOL"] ?? null) {
			if($slashPos = strpos($protocolString, "/")) {
				$protocolString = substr($protocolString, $slashPos + 1);
			}

			$request = $request->withProtocolVersion($protocolString);
		}

		return $request;
	}

	/**
	 * @param array<string, string> $server
	 */
	public function buildRequestHeaders(array $server):RequestHeaders {
		$headers = new RequestHeaders();
		foreach($server as $key => $value) {
			if(str_starts_with($key, "HTTP_")) {
				$headerKey = substr($key, strlen("HTTP_"));
				$headers->add($headerKey, $value);
			}
		}
		return $headers;
	}

	/**
	 * @param array<string, string> $server
	 */
	public function buildUri(array $server):UriInterface {
		$uri = new Uri($server["REQUEST_URI"] ?? null);

		if($server["HTTPS"] ?? null) {
			$uri = $uri->withScheme("https");
		}
		else {
			$uri = $uri->withScheme("http");
		}

		if($port = $server["SERVER_PORT"] ?? null) {
			$uri = $uri->withPort((int)$port);
		}

		if($host = $server["HTTP_HOST"] ?? null) {
			$host = strtok($host, ":");
			$uri = $uri->withHost($host);
		}

		if($query = $server["QUERY_STRING"] ?? null) {
			$uri = $uri->withQuery($query);
		}
		return $uri;
	}
}
