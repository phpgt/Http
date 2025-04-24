<?php /** @noinspection RegExpRedundantEscape */
namespace Gt\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/** @SuppressWarnings("ExcessiveClassComplexity") */
class Uri implements UriInterface {
	const DEFAULT_HOST_HTTP = "localhost";
	const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';
	const CHAR_SUBDELIMS = '!\$&\'\(\)\*\+,;=';

	protected string $scheme;
	protected string $userInfo;
	protected string $host;
	protected ?string $port;
	protected string $path;
	protected string $query;
	protected string $fragment;

	public function __construct(?string $uri = null) {
		if(is_null($uri)) {
			return;
		}

		$parts = parse_url($uri);
		if($parts === false) {
			throw new UriParseErrorException($uri);
		}
		$this->applyParts($parts);
	}

	/** @inheritDoc */
	public function __toString():string {
		$uri = "";
		$scheme = $this->getScheme();
		if(strlen($scheme) > 0) {
			$uri .= $scheme;
			$uri .= ":";
		}

		if(strlen($this->getAuthority()) > 0
			|| $scheme === "file") {
			$uri .= "//";
			$uri .= $this->getAuthority();
		}

		$uri .= $this->getPath();

		if(strlen($this->getQuery()) > 0) {
			$uri .= "?";
			$uri .= $this->getQuery();
		}

		if(strlen($this->getFragment()) > 0) {
			$uri .= "#";
			$uri .= $this->getFragment();
		}

		return $uri;
	}

	/** @param array<string, int|string> $parts */
	public function applyParts(array $parts):void {
		$this->scheme = $this->filterScheme((string)($parts["scheme"] ?? ""));

		$this->userInfo = $this->filterUserInfo(
			(string)($parts["user"] ?? ""),
			(string)($parts["pass"] ?? ""),
		);

		$this->host = $this->filterHost(
			(string)($parts["host"] ?? "")
		);
		$this->port = $this->filterPort(
			isset($parts["port"]) ? (int)$parts["port"] : null
		);
		$this->path = $this->filterPath(
			(string)($parts["path"] ?? "")
		);
		$this->query = $this->filterQueryAndFragment(
			(string)($parts["query"] ?? "")
		);
		$this->fragment = $this->filterQueryAndFragment(
			(string)($parts["fragment"] ?? "")
		);
		$this->setDefaults();
	}

	protected function filterScheme(string $scheme):string {
		return strtolower($scheme);
	}

	protected function filterHost(string $host):string {
		return strtolower($host);
	}

	protected function filterPort(?int $port = null):?string {
		if(is_null($port)) {
			return null;
		}

		if($port < 1 || $port > 0xffff) {
			throw new PortOutOfBoundsException((string)$port);
		}

		return (string)$port;
	}

	protected function filterPath(string $path):string {
		return preg_replace_callback(
			'/(?:[^'
			. self::CHAR_UNRESERVED
			. self::CHAR_SUBDELIMS
			. '%:@\/]++|%(?![A-Fa-f0-9]{2}))/',
			[$this, 'rawurlencodeMatchZero'],
			$path
		);
	}

	protected function filterQueryAndFragment(string $query):string {
		/** @noinspection RegExpUnnecessaryNonCapturingGroup */
		/** @noinspection RegExpDuplicateCharacterInClass */
		return preg_replace_callback(
			'/(?:[^'
			. self::CHAR_UNRESERVED
			. self::CHAR_SUBDELIMS
			. '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/',
			[$this, 'rawurlencodeMatchZero'],
			$query
		);
	}

	/** @param array<string> $match */
	protected function rawurlencodeMatchZero(array $match):string {
		return rawurlencode($match[0]);
	}

	protected function filterUserInfo(string $user, ?string $pass = null):string {
		$userInfo = $user;

		if(strlen($pass ?? "") > 0) {
			$userInfo .= ":";
			$userInfo .= $pass;
		}

		return $userInfo;
	}

	/** @inheritDoc */
	public function getScheme():string {
		return $this->scheme ?? "";
	}

	/** @inheritDoc */
	public function getAuthority():string {
		$authority = "";

		if(strlen($this->getUserInfo()) > 0) {
			$authority .= $this->getUserInfo();
			$authority .= "@";
		}

		$authority .= $this->getHost();

		if(!$this->isDefaultPort()) {
			$authority .= ":";
			$authority .= $this->getPort();
		}

		return $authority;
	}

	/** @inheritDoc */
	public function getUserInfo():string {
		return $this->userInfo ?? "";
	}

	/** @inheritDoc */
	public function getHost():string {
		return $this->host ?? "";
	}

	/** @inheritDoc */
	public function getPort():?int {
		if($this->isDefaultPort()) {
			return null;
		}

		return (int)$this->port;
	}

	/** @inheritDoc */
	public function getPath():string {
		return $this->path ?? "";
	}

	/** @inheritDoc */
	public function getQuery():string {
		return $this->query ?? "";
	}

	/** @return null|string|array<string, string> */
	public function getQueryValue(string $key):null|string|array {
		parse_str($this->getQuery(), $queryVariables);
		return $queryVariables[$key] ?? null;
	}

	/** @inheritDoc */
	public function getFragment():string {
		return $this->fragment ?? "";
	}

	/** @inheritDoc */
	public function withScheme(string $scheme):UriInterface {
		$scheme = $this->filterScheme($scheme);

		$clone = clone $this;
		$clone->scheme = $this->filterScheme($scheme);
		$clone->setDefaults();
		return $clone;
	}

	/** @inheritDoc */
	public function withUserInfo(string $user, ?string $password = null):self {
		$userInfo = $this->filterUserInfo($user, $password);

		$clone = clone $this;
		$clone->userInfo = $this->filterUserInfo($userInfo);
		$clone->setDefaults();
		return $clone;
	}

	/** @inheritDoc */
	public function withHost(string $host):self {
		$host = $this->filterHost($host);

		$clone = clone $this;
		$clone->host = $this->filterHost($host);
		$clone->setDefaults();
		return $clone;
	}

	/** @inheritDoc */
	public function withPort(?int $port):self {
		$port = $this->filterPort($port);

		$clone = clone $this;
		$clone->port = $port;
		$clone->setDefaults();
		return $clone;
	}

	/** @inheritDoc */
	public function withPath(string $path):self {
		$clone = clone $this;
		$clone->path = $this->filterPath($path);
		$clone->setDefaults();
		return $clone;
	}

	/** @inheritDoc */
	public function withQuery(string $query):self {
		$clone = clone $this;
		$clone->query = $this->filterQueryAndFragment($query);
		$clone->setDefaults();
		return $clone;
	}

	public function withQueryValue(string $key, ?string $value = null):self {
// TODO: Hotspot for refactoring opportunity.
// http_build_query should help simplify all of this messy code.
// Note limitation of http_build_query can be resolved using $replaceQuery below
		$replaceQuery = ["=" => "%3D", "&" => "%26", "^" => "%5E"];
		$current = $this->getQuery();

		if($current === "") {
			$result = [];
		}
		else {
			$decodedKey = rawurldecode($key);

			$result = array_filter(
				explode("&", $current),
				function($part) use ($decodedKey) {
					return rawurldecode(
							explode("=", $part)[0]
						) !== $decodedKey;
				});
		}
// Query string separators ("=", "&") within the key or value need to be encoded
// (while preventing double-encoding) before setting the query string. All other
// chars that need percent-encoding will be encoded by withQuery().

// This function is taken from Guzzle's Uri implementation, just to get tests
// to pass.
// It must be refactored before v1 release, as it has a major bug as shown here:
		$key = strtr($key, $replaceQuery);
		if($value !== null) {
			$result[] = $key . "=" . strtr($value, $replaceQuery);
		}
		else {
			$result[] = $key;
		}

		$this->setDefaults();
		return $this->withQuery(implode("&", $result));
	}

	public function withoutQueryValue(string $key):self {
		$current = $this->getQuery();

		$decodedKey = rawurldecode($key);
		$result = array_filter(
			explode("&", $current),
			function($part) use ($decodedKey) {
				return rawurldecode(
						explode("=", $part)[0]
					) !== $decodedKey;
			});

		$this->setDefaults();
		return $this->withQuery(implode("&", $result));
	}

	/** @inheritDoc */
	public function withFragment(string $fragment):self {
		$clone = clone $this;
		$clone->fragment = $this->filterQueryAndFragment($fragment);
		$clone->setDefaults();
		return $clone;
	}

	public function isDefaultPort():bool {
		$scheme = $this->getScheme();
		$port = $this->port ?? null;

		if(empty($port)) {
			return true;
		}

		$defaultPortConstant = "\\Gt\\Http\\DefaultPort::" . strtoupper($scheme);
		$defaultPort = null;

		if(defined($defaultPortConstant)) {
			$defaultPort = constant($defaultPortConstant);
		}

		return ($defaultPort == $port);
	}

	public function isAbsolute():bool {
		return $this->getScheme() !== "";
	}

	public function isNetworkPathReference():bool {
		return $this->getScheme() === ""
			&& $this->getAuthority() !== "";
	}

	public function isAbsolutePathReference():bool {
		return $this->getScheme() === ""
			&& $this->getAuthority() === ""
			&& isset($this->getPath()[0])
			&& $this->getPath()[0] === "/";
	}

	public function isRelativePathReference():bool {
		return $this->getScheme() === ""
			&& $this->getAuthority() === ""
			&& (!isset($this->getPath()[0]) || $this->getPath()[0] !== '/');
	}

	public function isSameDocumentReference(?Uri $baseUri = null):bool {
		if(!is_null($baseUri)) {
			$resolver = new UriResolver();
			$resolved = $resolver->resolve($baseUri, $this);

			return $resolved->getScheme() === $baseUri->getScheme()
				&& $resolved->getAuthority() === $baseUri->getAuthority()
				&& $resolved->getPath() === $baseUri->getPath()
				&& $resolved->getQuery() === $baseUri->getQuery();
		}

		return $this->getScheme() === ""
			&& $this->getAuthority() === ""
			&& $this->getPath() === ""
			&& $this->getQuery() === "";
	}

	protected function setDefaults():void {
		if(strlen($this->getHost()) === 0) {
			if(str_starts_with($this->getScheme(), "http")) {
				$this->host = self::DEFAULT_HOST_HTTP;
			}
		}

		if($this->getAuthority() === "") {
			if(str_starts_with($this->getPath(), "//")) {
				throw new InvalidArgumentException(
					"The path of a URI without an authority "
					. "must not start with two slashes \"//\""
				);
			}
			if($this->getScheme() === ""
				&& strpos(explode('/', $this->getPath(), 2)[0], ':')) {
				throw new InvalidArgumentException(
					"A relative URI must not have a path beginning "
					. "with a segment containing a colon"
				);
			}
		}
		else {
			if(strlen($this->getPath()) > 0
				&& $this->getPath()[0] !== "/") {
				$this->path = "/" . $this->getPath();
			}
		}
	}
}
