<?php
namespace GT\Http;
use Psr\Http\Message\UriInterface;

/**
 * @see http://php.net/manual/en/reserved.variables.server.php
 * @SuppressWarnings("ExcessiveClassComplexity")
 */
class ServerInfo {
	/** @var array<string, string> The original _server array */
	protected array $server;

	/** @param array<string, string> $server */
	public function __construct(array $server) {
		$this->server = $server;
	}

// Non-nullable values: --------------------------------------------------------
	/**
	 * HTTP headers are case-insensitive, so headers are transformed to
	 * uppercase.
	 * @link https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
	 * @return array<string, string>
	 */
	public function getHttpHeadersArray():array {
		$headers = [];

		foreach($this->server as $key => $value) {
			if(!str_starts_with($key, "HTTP_")) {
				continue;
			}

			$headerName = substr($key, strlen("HTTP_"));
			$headerName = strtoupper($headerName);
			$headerName = str_replace("_", "-", $headerName);
			$headers[$headerName] = $value;
		}

		return $headers;
	}

	/**
	 * Name and revision of the information protocol via which the page
	 * was requested.
	 */
	public function getServerProtocol():string {
		return $this->server["SERVER_PROTOCOL"];
	}

	public function getServerProtocolVersion():float {
		$version = filter_var(
			$this->getServerProtocol(),
			FILTER_SANITIZE_NUMBER_FLOAT,
			FILTER_FLAG_ALLOW_FRACTION
		);
		return (float)$version;
	}

	/**
	 * The timestamp of the start of the request, with microsecond
	 * precision.
	 */
	public function getRequestMethod():string {
		return $this->server["REQUEST_METHOD"];
	}

	/**
	 * The timestamp of the start of the request, with microsecond
	 * precision.
	 */
	public function getRequestTime():float {
		return (float)$this->server["REQUEST_TIME_FLOAT"];
	}

	/**
	 * The query string, if any, via which the page was accessed.
	 */
	public function getQueryString():string {
		return $this->server["QUERY_STRING"] ?? "";
	}

	public function withQueryString(string $queryString):self {
		$clone = clone $this;
		$clone->server["QUERY_STRING"] = $queryString;
		return $clone;
	}

	/**
	 * The deserialized query string arguments, if any.
	 * @return array<string, string|array<string>>
	 */
	public function getQueryParams():array {
		$params = [];

		$queryString = $this->getQueryString();
		parse_str($queryString, $params);
		/** @var array<string, string|array<string>> $params */
		return $params;
	}

	/** @param array<string, string> $query */
	public function withQueryParams(array $query):self {
		$queryString = http_build_query($query);
		return $this->withQueryString($queryString);
	}

	/**
	 * The document root directory under which the current script is
	 * executing, as defined in the server's configuration file.
	 */
	public function getDocumentRoot():string {
		return $this->server["DOCUMENT_ROOT"];
	}

	/**
	 * If the script was queried through the HTTPS protocol.
	 */
	public function isHttps():bool {
		return !empty($this->server["HTTPS"]);
	}

	/**
	 * The IP address from which the user is viewing the current page.
	 */
	public function getRemoteAddress():string {
		return $this->server["REMOTE_ADDR"];
	}

	/**
	 * The absolute pathname of the currently executing script.
	 */
	public function getScriptFilename():string {
		return $this->server["SCRIPT_FILENAME"];
	}

	/**
	 * Contains the current script's path. This is useful for pages which
	 * need to point to themselves.
	 */
	public function getScriptName():string {
		return $this->server["SCRIPT_NAME"];
	}

	/**
	 * The URI which was given in order to access this page.
	 */
	public function getRequestUri():UriInterface {
		$uri = new Uri();

		if(isset($this->server["HTTPS"])) {
			$uri = $uri->withScheme("https");
		}
		else {
			$uri = $uri->withScheme("http");
		}

		if(isset($this->server["HTTP_HOST"])) {
			$uri = $uri->withHost(
				strtok($this->server["HTTP_HOST"], ":") ?: ""
			);
		}

		if(isset($this->server["SERVER_PORT"])) {
			$uri = $uri->withPort((int)$this->server["SERVER_PORT"]);
		}

		if(isset($this->server["REQUEST_URI"])) {
			$uri = $uri->withPath(
				strtok($this->server["REQUEST_URI"], "?") ?: ""
			);
		}

		if(isset($this->server["QUERY_STRING"])) {
			$uri = $uri->withQuery($this->server["QUERY_STRING"]);
		}

		return $uri;
	}

	public function getFullUri():UriInterface {
		$scheme = $this->getRequestScheme();
		if(!$scheme) {
			$scheme = "http";
			if($this->isHttps()) {
				$scheme .= "s";
			}
		}

		$host = $this->getHttpHost();
		$port = "";

		if(strstr($host, ":") === false
		&& $this->getServerPort() !== 80) {
			$port = ":" . $this->getServerPort();
		}

		$path = $this->getRequestUri()->getPath();
		$query = "";
		$queryParams = $this->getQueryParams();

		if(!empty($queryParams)) {
			$query = "?";
			$query .= http_build_query($queryParams);
		}

		$uri = new Uri(
			$scheme
			. "://"
			. $host
			. $port
			. $path
			. $query
		);
		return $uri;
	}

// Nullable values: --------------------------------------------------------------------------------

	/**
	 * The filename of the currently executing script, relative to the
	 * document root.
	 */
	public function getPhpSelf():?string {
		return $this->server["PHP_SELF"] ?? null;
	}

	/**
	 * What revision of the CGI specification the server is using;
	 * i.e. 'CGI/1.1'.
	 */
	public function getGatewayInterface():?string {
		return $this->server["GATEWAY_INTERFACE"] ?? null;
	}

	/**
	 * The IP address of the server under which the current script
	 * is executing.
	 */
	public function getServerAddress():?string {
		return $this->server["SERVER_ADDR"] ?? null;
	}

	/**
	 * The name of the server host under which the current script
	 * is executing.
	 */
	public function getServerName():?string {
		return $this->server["SERVER_NAME"] ?? null;
	}

	/**
	 * Server identification string, given in the headers when responding
	 * to requests.
	 */
	public function getServerSoftware():?string {
		return $this->server["SERVER_SOFTWARE"] ?? null;
	}

	/**
	 * The Host name from which the user is viewing the current page.
	 * The reverse dns lookup is based off the REMOTE_ADDR of the user.
	 */
	public function getRemoteHost():?string {
		return $this->server["REMOTE_HOST"] ?? null;
	}

	/**
	 * The Host name of the server that is serving the current page. This
	 * is the value of the HTTP "host" header, without the port.
	 */
	public function getServerHost():?string {
		$host = $this->server["HTTP_HOST"] ?? null;

		if($host) {
			$host = strtok($host, ":");
		}

		return $host;
	}

	/**
	 * The Host name of the server that is serving the current page. This
	 * is the value of the HTTP "host" header, typically sent from the
	 * browser. Ports other than 80 are included after a colon.
	 */
	public function getHttpHost():?string {
		$host = $this->server["HTTP_HOST"] ?? null;
		return $host;
	}

	/**
	 * The port being used on the user's machine to communicate with
	 * the web server.
	 */
	public function getRemotePort():?int {
		if($port = $this->server["REMOTE_PORT"] ?? null) {
			return (int)$port;
		}

		return null;
	}

	/**
	 * The authenticated user.
	 */
	public function getRemoteUser():?string {
		return $this->server["REMOTE_USER"] ?? null;
	}

	/**
	 * The authenticated user if the request is internally redirected.
	 */
	public function getRedirectRemoteUser():?string {
		return $this->server["REDIRECT_REMOTE_USER"] ?? null;
	}

	/**
	 * The value given to the SERVER_ADMIN (for Apache) directive in the
	 * web server
	 * configuration file.
	 */
	public function getServerAdmin():?string {
		return $this->server["SERVER_ADMIN"] ?? null;
	}

	/**
	 * The port on the server machine being used by the web server for
	 * communication.
	 */
	public function getServerPort():?int {
		if($port = $this->server["SERVER_PORT"] ?? null) {
			return (int)$port;
		}

		return null;
	}

	/**
	 * ng containing the server version and virtual host name which are
	 * added to server-generated pages, if enabled.
	 */
	public function getServerSignature():?string {
		return $this->server["SERVER_SIGNATURE"] ?? null;
	}

	/**
	 * When doing Digest HTTP authentication this variable is set to the
	 * 'Authorization' header sent by the client (which you should then use
	 * to make the appropriate validation).
	 */
	public function getAuthDigest():?string {
		return $this->server["PHP_AUTH_DIGEST"] ?? null;
	}

	/**
	 * When doing HTTP authentication this variable is set to the username
	 * provided by the user.
	 */
	public function getAuthUser():?string {
		return $this->server["PHP_AUTH_USER"] ?? null;
	}

	/**
	 * When doing HTTP authentication this variable is set to the password
	 * provided by the user.
	 */
	public function getAuthPassword():?string {
		return $this->server["PHP_AUTH_PW"] ?? null;
	}

	/**
	 * When doing HTTP authentication this variable is set to the
	 * authentication type.
	 */
	public function getAuthType():?string {
		return $this->server["AUTH_TYPE"] ?? null;
	}

	/**
	 * The scheme which was given to access this page, if any.
	 */
	public function getRequestScheme():?string {
		return $this->server["REQUEST_SCHEME"] ?? null;
	}

	/** @return array<string, string> */
	public function getParams():array {
		return $this->server;
	}
}
