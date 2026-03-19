<?php
namespace Gt\Http\Test;

use Exception;
use Gt\Http\PortOutOfBoundsException;
use Gt\Http\Uri;
use Gt\Http\UriFactory;
use Gt\Http\UriParseErrorException;
use PHPUnit\Framework\TestCase;
use TypeError;

class UriTest extends TestCase {
	public function testParsesProvidedUri() {
		$uri = new Uri('https://user:pass@example.com:8080/path/123?q=abc#test');
		$this->assertSame('https', $uri->getScheme());
		$this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
		$this->assertSame('user:pass', $uri->getUserInfo());
		$this->assertSame('example.com', $uri->getHost());
		$this->assertSame(8080, $uri->getPort());
		$this->assertSame('/path/123', $uri->getPath());
		$this->assertSame('q=abc', $uri->getQuery());
		$this->assertSame('test', $uri->getFragment());
		$this->assertSame('https://user:pass@example.com:8080/path/123?q=abc#test', (string)$uri);
	}

	public function testCanTransformAndRetrievePartsIndividually() {
		$uri = (new Uri())
			->withScheme('https')
			->withUserInfo('user', 'pass')
			->withHost('example.com')
			->withPort(8080)
			->withPath('/path/123')
			->withQuery('q=abc')
			->withFragment('test');
		$this->assertSame('https', $uri->getScheme());
		$this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
		$this->assertSame('user:pass', $uri->getUserInfo());
		$this->assertSame('example.com', $uri->getHost());
		$this->assertSame(8080, $uri->getPort());
		$this->assertSame('/path/123', $uri->getPath());
		$this->assertSame('q=abc', $uri->getQuery());
		$this->assertSame('test', $uri->getFragment());
		$this->assertSame('https://user:pass@example.com:8080/path/123?q=abc#test', (string)$uri);
	}

	/** @dataProvider getValidUris */
	public function testValidUrisStayValid($input) {
		$uri = new Uri($input);
		self::assertSame($input, (string)$uri);
	}

	/** @dataProvider getValidUris */
	public function testFromParts($input) {
		$uri = (new UriFactory())->createFromParts(parse_url($input));
		self::assertSame($input, (string)$uri);
	}

	public static function getValidUris():array {
		return [
			['urn:path-rootless'],
			['urn:path:with:colon'],
			['urn:/path-absolute'],
			['urn:/'],
			// only scheme with empty path
			['urn:'],
			// only path
			['/'],
			['relative/'],
			['0'],
			// same document reference
			[''],
			// network path without scheme
			['//example.org'],
			['//example.org/'],
			['//example.org?q#h'],
			// only query
			['?q'],
			['?q=abc&foo=bar'],
			// only fragment
			['#fragment'],
			// dot segments are not removed automatically
			['./foo/../bar'],
		];
	}

	/** @dataProvider getInvalidUris */
	public function testInvalidUrisThrowException($invalidUri) {
		self::expectException(UriParseErrorException::class);
		new Uri($invalidUri);
	}

	public static function getInvalidUris() {
		return [
			// parse_url() requires the host component which makes sense for http(s)
			// but not when the scheme is not known or different. So '//' or '///' is
			// currently invalid as well but should not according to RFC 3986.
			['http://'],
			['urn://host:with:colon'], // host cannot contain ":"
		];
	}

	public function testPortMustBeValid() {
		self::expectException(PortOutOfBoundsException::class);
		(new Uri())->withPort(100000);
	}

	public function testWithPortCannotBeZero() {
		self::expectException(PortOutOfBoundsException::class);
		(new Uri())->withPort(0);
	}

	public function testParseUriPortCannotBeZero() {
		self::expectException(PortOutOfBoundsException::class);
		new Uri('//example.com:0');
	}

	public function testSchemeMustHaveCorrectType() {
		self::expectException(TypeError::class);
		(new Uri())->withScheme([]);
	}

	public function testHostMustHaveCorrectType() {
		self::expectException(TypeError::class);
		(new Uri())->withHost([]);
	}

	public function testPathMustHaveCorrectType() {
		self::expectException(TypeError::class);
		(new Uri())->withPath([]);
	}

	public function testQueryMustHaveCorrectType() {
		self::expectException(TypeError::class);
		(new Uri())->withQuery([]);
	}

	public function testFragmentMustHaveCorrectType() {
		self::expectException(TypeError::class);
		(new Uri())->withFragment([]);
	}

	public function testCanParseFalseyUriParts() {
		$uri = new Uri('0://0:0@0/0?0#0');
		$this->assertSame('0', $uri->getScheme());
		$this->assertSame('0:0@0', $uri->getAuthority());
		$this->assertSame('0:0', $uri->getUserInfo());
		$this->assertSame('0', $uri->getHost());
		$this->assertSame('/0', $uri->getPath());
		$this->assertSame('0', $uri->getQuery());
		$this->assertSame('0', $uri->getFragment());
		$this->assertSame('0://0:0@0/0?0#0', (string)$uri);
	}

	public function testCanConstructFalseyUriParts() {
		$uri = (new Uri())
			->withScheme('0')
			->withUserInfo('0', '0')
			->withHost('0')
			->withPath('/0')
			->withQuery('0')
			->withFragment('0');
		$this->assertSame('0', $uri->getScheme());
		$this->assertSame('0:0@0', $uri->getAuthority());
		$this->assertSame('0:0', $uri->getUserInfo());
		$this->assertSame('0', $uri->getHost());
		$this->assertSame('/0', $uri->getPath());
		$this->assertSame('0', $uri->getQuery());
		$this->assertSame('0', $uri->getFragment());
		$this->assertSame('0://0:0@0/0?0#0', (string)$uri);
	}

	public function testParsesAuthorityStyleCredentialsWithoutScheme() {
		$uri = new Uri('admin:admin@10.10.0.8/status.xml');
		$this->assertSame('', $uri->getScheme());
		$this->assertSame('admin:admin', $uri->getUserInfo());
		$this->assertSame('10.10.0.8', $uri->getHost());
		$this->assertSame('/status.xml', $uri->getPath());
	}

	public function testBuildsAuthorityFromCredentialsWithoutScheme() {
		$uri = new Uri('admin:admin@10.10.0.8/status.xml');
		$this->assertSame('admin:admin@10.10.0.8', $uri->getAuthority());
	}

	public function testParsesAuthorityStyleUserWithoutPasswordAndWithoutScheme() {
		$uri = new Uri('admin@10.10.0.8/status.xml');
		$this->assertSame('', $uri->getScheme());
		$this->assertSame('admin', $uri->getUserInfo());
		$this->assertSame('10.10.0.8', $uri->getHost());
		$this->assertSame('/status.xml', $uri->getPath());
	}

	public function testParsesLocalhostWithoutSchemeAsHost() {
		$uri = new Uri('localhost/status');
		$this->assertSame('localhost', $uri->getHost());
		$this->assertSame('/status', $uri->getPath());
	}

	public function testKeepsSingleLabelPathWithoutSchemeAsRelativePath() {
		$uri = new Uri('printer/status');
		$this->assertSame('', $uri->getHost());
		$this->assertSame('printer/status', $uri->getPath());
	}

	public function testParsesIpv6HostAndPortWithoutScheme() {
		$uri = new Uri('[2001:db8::1]:8080/a');
		$this->assertSame('[2001:db8::1]', $uri->getHost());
		$this->assertSame(8080, $uri->getPort());
		$this->assertSame('/a', $uri->getPath());
	}

	public function testParsesHostPortAndQueryWithoutScheme() {
		$uri = new Uri('10.0.0.1:4321?x=1');
		$this->assertSame('10.0.0.1', $uri->getHost());
		$this->assertSame(4321, $uri->getPort());
		$this->assertSame('', $uri->getPath());
		$this->assertSame('x=1', $uri->getQuery());
	}

	public function testKeepsOriginalParseWhenAuthorityFallbackCannotParseDoubleSlash() {
		$uri = new Uri('admin:admin@?/x');
		$this->assertSame('admin', $uri->getScheme());
		$this->assertSame('admin@', $uri->getPath());
		$this->assertSame('/x', $uri->getQuery());
	}

	public function testKeepsOriginalParseWhenAuthorityHostIsNotHostLike() {
		$uri = new Uri('admin:admin@example/status.xml');
		$this->assertSame('admin', $uri->getScheme());
		$this->assertSame('', $uri->getAuthority());
		$this->assertSame('admin@example/status.xml', $uri->getPath());
	}

	public function testCanRejectAuthorityFallbackWhenRequiredAuthorityPartsAreMissing() {
		$uri = new class('admin:admin@10.10.0.8/status.xml') extends Uri {
			protected function hasRequiredAuthorityParts(array $authorityParts):bool {
				return false;
			}
		};

		$this->assertSame('admin', $uri->getScheme());
		$this->assertSame('', $uri->getAuthority());
		$this->assertSame('admin@10.10.0.8/status.xml', $uri->getPath());
	}

	public function testCanRejectAuthorityFallbackWhenAuthorityPathIsInvalid() {
		$uri = new class('admin:admin@10.10.0.8/status.xml') extends Uri {
			protected function isAuthorityPathValid(array $authorityParts):bool {
				return false;
			}
		};

		$this->assertSame('admin', $uri->getScheme());
		$this->assertSame('', $uri->getAuthority());
		$this->assertSame('admin@10.10.0.8/status.xml', $uri->getPath());
	}

	/**
	 * @dataProvider getPortTestCases
	 */
	public function testIsDefaultPort($scheme, $port, $isDefaultPort) {
		$uri = (new Uri())
			->withScheme($scheme)
			->withPort($port);

		$this->assertSame(
			$isDefaultPort,
			$uri->isDefaultPort(),
			"Scheme: $scheme - Port: $port"
		);
	}

	public static function getPortTestCases() {
		return [
			['http', null, true],
			['http', 80, true],
			['http', 8080, false],
			['https', null, true],
			['https', 443, true],
			['https', 444, false],
			['ftp', 21, true],
			['gopher', 70, true],
			['nntp', 119, true],
			['news', 119, true],
			['telnet', 23, true],
			['tn3270', 23, true],
			['imap', 143, true],
			['pop', 110, true],
			['ldap', 389, true],
		];
	}

	public function testIsAbsolute() {
		$this->assertTrue((new Uri('http://example.org'))->isAbsolute());
		$this->assertFalse((new Uri('//example.org'))->isAbsolute());
		$this->assertFalse((new Uri('/abs-path'))->isAbsolute());
		$this->assertFalse((new Uri('rel-path'))->isAbsolute());
	}

	public function testIsNetworkPathReference() {
		$this->assertFalse((new Uri('http://example.org'))->isNetworkPathReference());
		$this->assertTrue((new Uri('//example.org'))->isNetworkPathReference());
		$this->assertFalse((new Uri('/abs-path'))->isNetworkPathReference());
		$this->assertFalse((new Uri('rel-path'))->isNetworkPathReference());
	}

	public function testIsAbsolutePathReference() {
		$this->assertFalse((new Uri('http://example.org'))->isAbsolutePathReference());
		$this->assertFalse((new Uri('//example.org'))->isAbsolutePathReference());
		$this->assertTrue((new Uri('/abs-path'))->isAbsolutePathReference());
		$this->assertTrue((new Uri('/'))->isAbsolutePathReference());
		$this->assertFalse((new Uri('rel-path'))->isAbsolutePathReference());
	}

	public function testIsRelativePathReference() {
		$this->assertFalse((new Uri('http://example.org'))->isRelativePathReference());
		$this->assertFalse((new Uri('//example.org'))->isRelativePathReference());
		$this->assertFalse((new Uri('/abs-path'))->isRelativePathReference());
		$this->assertTrue((new Uri('rel-path'))->isRelativePathReference());
		$this->assertTrue((new Uri(''))->isRelativePathReference());
	}

	public function testIsSameDocumentReference() {
		$this->assertFalse((new Uri('http://example.org'))->isSameDocumentReference());
		$this->assertFalse((new Uri('//example.org'))->isSameDocumentReference());
		$this->assertFalse((new Uri('/abs-path'))->isSameDocumentReference());
		$this->assertFalse((new Uri('rel-path'))->isSameDocumentReference());
		$this->assertFalse((new Uri('?query'))->isSameDocumentReference());
		$this->assertTrue((new Uri(''))->isSameDocumentReference());
		$this->assertTrue((new Uri('#fragment'))->isSameDocumentReference());
		$baseUri = new Uri('http://example.org/path?foo=bar');
		$this->assertTrue((new Uri('#fragment'))->isSameDocumentReference($baseUri));
		$this->assertTrue((new Uri('?foo=bar#fragment'))->isSameDocumentReference($baseUri));
		$this->assertTrue((new Uri('/path?foo=bar#fragment'))->isSameDocumentReference($baseUri));
		$this->assertTrue((new Uri('path?foo=bar#fragment'))->isSameDocumentReference($baseUri));
		$this->assertTrue((new Uri('//example.org/path?foo=bar#fragment'))->isSameDocumentReference($baseUri));
		$this->assertTrue((new Uri('http://example.org/path?foo=bar#fragment'))->isSameDocumentReference($baseUri));
		$this->assertFalse((new Uri('https://example.org/path?foo=bar'))->isSameDocumentReference($baseUri));
		$this->assertFalse((new Uri('http://example.com/path?foo=bar'))->isSameDocumentReference($baseUri));
		$this->assertFalse((new Uri('http://example.org/'))->isSameDocumentReference($baseUri));
		$this->assertFalse((new Uri('http://example.org'))->isSameDocumentReference($baseUri));
		$this->assertFalse((new Uri('urn:/path'))->isSameDocumentReference(new Uri('urn://example.com/path')));
	}

	public function testAddAndRemoveQueryValues() {
		$uri = new Uri();
		$uri = $uri->withQueryValue('a', 'b');
		$uri = $uri->withQueryValue('c', 'd');
		$uri = $uri->withQueryValue('e', null);
		$this->assertSame('a=b&c=d&e', $uri->getQuery());

		$uri = $uri->withoutQueryValue('c');
		$this->assertSame('a=b&e', $uri->getQuery());
		$uri = $uri->withoutQueryValue('e');
		$this->assertSame('a=b', $uri->getQuery());
		$uri = $uri->withoutQueryValue('a');
		$this->assertSame('', $uri->getQuery());
	}

	public function testWithQueryValueReplacesSameKeys() {
		$uri = new Uri();
		$uri = $uri->withQueryValue('a', 'b');
		$uri = $uri->withQueryValue('c', 'd');
		$uri = $uri->withQueryValue('a', 'e');
		$this->assertSame('c=d&a=e', $uri->getQuery());
	}

	public function testWithoutQueryValueRemovesAllSameKeys() {
		$uri = (new Uri())->withQuery('a=b&c=d&a=e');
		$uri = $uri->withoutQueryValue('a');
		$this->assertSame('c=d', $uri->getQuery());
	}

	public function testRemoveNonExistingQueryValue() {
		$uri = new Uri();
		$uri = $uri->withQueryValue('a', 'b');
		$uri = $uri->withoutQueryValue('c');
		$this->assertSame('a=b', $uri->getQuery());
	}

	public function testWithQueryValueHandlesEncoding() {
		$uri = new Uri();
		$uri = $uri->withQueryValue('E=mc^2', 'ein&stein');
		$this->assertSame('E%3Dmc%5E2=ein%26stein', $uri->getQuery(), 'Decoded key/value get encoded');
		$uri = new Uri();
		$uri = $uri->withQueryValue('E%3Dmc%5e2', 'ein%26stein');
		$this->assertSame('E%3Dmc%5e2=ein%26stein', $uri->getQuery(), 'Encoded key/value do not get double-encoded');
	}

	public function testWithoutQueryValueHandlesEncoding() {
		// It also tests that the case of the percent-encoding does not matter,
		// i.e. both lowercase "%3d" and uppercase "%5E" can be removed.
		$uri = (new Uri())->withQuery('E%3dmc%5E2=einstein&foo=bar');
		$uri = $uri->withoutQueryValue('E=mc^2');
		$this->assertSame('foo=bar', $uri->getQuery(), 'Handles key in decoded form');
		$uri = (new Uri())->withQuery('E%3dmc%5E2=einstein&foo=bar');
		$uri = $uri->withoutQueryValue('E%3Dmc%5e2');
		$this->assertSame('foo=bar', $uri->getQuery(), 'Handles key in encoded form');
	}

	public function testSchemeIsNormalizedToLowercase() {
		$uri = new Uri('HTTP://example.com');
		$this->assertSame('http', $uri->getScheme());
		$this->assertSame('http://example.com', (string)$uri);
		$uri = (new Uri('//example.com'))->withScheme('HTTP');
		$this->assertSame('http', $uri->getScheme());
		$this->assertSame('http://example.com', (string)$uri);
	}

	public function testHostIsNormalizedToLowercase() {
		$uri = new Uri('//eXaMpLe.CoM');
		$this->assertSame('example.com', $uri->getHost());
		$this->assertSame('//example.com', (string)$uri);
		$uri = (new Uri())->withHost('eXaMpLe.CoM');
		$this->assertSame('example.com', $uri->getHost());
		$this->assertSame('//example.com', (string)$uri);
	}

	public function testPortIsNullIfStandardPortForScheme() {
		// HTTPS standard port
		$uri = new Uri('https://example.com:443');
		$this->assertNull($uri->getPort());
		$this->assertSame('example.com', $uri->getAuthority());
		$uri = (new Uri('https://example.com'))->withPort(443);
		$this->assertNull($uri->getPort());
		$this->assertSame('example.com', $uri->getAuthority());
		// HTTP standard port
		$uri = new Uri('http://example.com:80');
		$this->assertNull($uri->getPort());
		$this->assertSame('example.com', $uri->getAuthority());
		$uri = (new Uri('http://example.com'))->withPort(80);
		$this->assertNull($uri->getPort());
		$this->assertSame('example.com', $uri->getAuthority());
	}

	public function testPortIsReturnedIfSchemeUnknown() {
		$uri = (new Uri('//example.com'))->withPort(80);
		$this->assertSame(80, $uri->getPort());
		$this->assertSame('example.com:80', $uri->getAuthority());
	}

	public function testStandardPortIsNullIfSchemeChanges() {
		$uri = new Uri('http://example.com:443');
		$this->assertSame('http', $uri->getScheme());
		$this->assertSame(443, $uri->getPort());
		$uri = $uri->withScheme('https');
		$this->assertNull($uri->getPort());
	}

	public function testPortPassedAsStringIsCastedToInt() {
		$exception = null;
		try {
			(new Uri('//example.com'))->withPort("8080");
		}
		catch(Exception $exception) {}
		self::assertNull($exception);
	}

	public function testPortCanBeRemoved() {
		$uri = (new Uri('http://example.com:8080'))->withPort(null);
		$this->assertNull($uri->getPort());
		$this->assertSame('http://example.com', (string)$uri);
	}

	/**
	 * In RFC 8986 the host is optional and the authority can only
	 * consist of the user info and port.
	 */
	public function testAuthorityWithUserInfoOrPortButWithoutHost() {
		$uri = (new Uri())->withUserInfo('user', 'pass');
		$this->assertSame('user:pass', $uri->getUserInfo());
		$this->assertSame('user:pass@', $uri->getAuthority());
		$uri = $uri->withPort(8080);
		$this->assertSame(8080, $uri->getPort());
		$this->assertSame('user:pass@:8080', $uri->getAuthority());
		$this->assertSame('//user:pass@:8080', (string)$uri);
		$uri = $uri->withUserInfo('');
		$this->assertSame(':8080', $uri->getAuthority());
	}

	public function testHostInHttpUriDefaultsToLocalhost() {
		$uri = (new Uri())->withScheme('http');
		$this->assertSame('localhost', $uri->getHost());
		$this->assertSame('localhost', $uri->getAuthority());
		$this->assertSame('http://localhost', (string)$uri);
	}

	public function testHostInHttpsUriDefaultsToLocalhost() {
		$uri = (new Uri())->withScheme('https');
		$this->assertSame('localhost', $uri->getHost());
		$this->assertSame('localhost', $uri->getAuthority());
		$this->assertSame('https://localhost', (string)$uri);
	}

	public function testFileSchemeWithEmptyHostReconstruction() {
		$uri = new Uri('file:///tmp/filename.ext');
		$this->assertSame('', $uri->getHost());
		$this->assertSame('', $uri->getAuthority());
		$this->assertSame('file:///tmp/filename.ext', (string)$uri);
	}

	public static function uriComponentsEncodingProvider() {
		$unreserved = 'a-zA-Z0-9.-_~!$&\'()*+,;=:@';

		return [
			// Percent encode spaces
			[
				'/pa th?q=va lue#frag ment',
				'/pa%20th',
				'q=va%20lue',
				'frag%20ment',
				'/pa%20th?q=va%20lue#frag%20ment'
			],
			// Percent encode multibyte
			[
				'/€?€#€',
				'/%E2%82%AC',
				'%E2%82%AC',
				'%E2%82%AC',
				'/%E2%82%AC?%E2%82%AC#%E2%82%AC'
			],
			// Don't encode something that's already encoded
			[
				'/pa%20th?q=va%20lue#frag%20ment',
				'/pa%20th',
				'q=va%20lue',
				'frag%20ment',
				'/pa%20th?q=va%20lue#frag%20ment'
			],
			// Percent encode invalid percent encodings
			[
				'/pa%2-th?q=va%2-lue#frag%2-ment',
				'/pa%252-th',
				'q=va%252-lue',
				'frag%252-ment',
				'/pa%252-th?q=va%252-lue#frag%252-ment'
			],
			// Don't encode path segments
			[
				'/pa/th//two?q=va/lue#frag/ment',
				'/pa/th//two',
				'q=va/lue',
				'frag/ment',
				'/pa/th//two?q=va/lue#frag/ment'
			],
			// Don't encode unreserved chars or sub-delimiters
			[
				"/$unreserved?$unreserved#$unreserved",
				"/$unreserved",
				$unreserved,
				$unreserved,
				"/$unreserved?$unreserved#$unreserved"
			],
			// Encoded unreserved chars are not decoded
			[
				'/p%61th?q=v%61lue#fr%61gment',
				'/p%61th',
				'q=v%61lue',
				'fr%61gment',
				'/p%61th?q=v%61lue#fr%61gment'
			],
		];
	}

	/**
	 * @dataProvider uriComponentsEncodingProvider
	 */
	public static function testUriComponentsGetEncodedProperly(
		$input,
		$path,
		$query,
		$fragment,
		$output
	) {
		$uri = new Uri($input);
		self::assertSame($path, $uri->getPath());
		self::assertSame($query, $uri->getQuery());
		self::assertSame($fragment, $uri->getFragment());
		self::assertSame($output, (string)$uri);
	}

	public function testWithPathEncodesProperly() {
		$uri = (new Uri())->withPath('/baz?#€/b%61r');
		// Query and fragment delimiters and multibyte chars are encoded.
		$this->assertSame('/baz%3F%23%E2%82%AC/b%61r', $uri->getPath());
		$this->assertSame('/baz%3F%23%E2%82%AC/b%61r', (string)$uri);
	}

	public function testWithQueryEncodesProperly() {
		$uri = (new Uri())->withQuery('?=#&€=/&b%61r');
		// A query starting with a "?" is valid and must not be magically removed. Otherwise it would be impossible to
		// construct such an URI. Also the "?" and "/" does not need to be encoded in the query.
		$this->assertSame('?=%23&%E2%82%AC=/&b%61r', $uri->getQuery());
		$this->assertSame('??=%23&%E2%82%AC=/&b%61r', (string)$uri);
	}

	public function testWithFragmentEncodesProperly() {
		$uri = (new Uri())->withFragment('#€?/b%61r');
		// A fragment starting with a "#" is valid and must not be magically removed. Otherwise it would be impossible to
		// construct such an URI. Also the "?" and "/" does not need to be encoded in the fragment.
		$this->assertSame('%23%E2%82%AC?/b%61r', $uri->getFragment());
		$this->assertSame('#%23%E2%82%AC?/b%61r', (string)$uri);
	}

	public function testAllowsForRelativeUri() {
		$uri = (new Uri)->withPath('foo');
		$this->assertSame('foo', $uri->getPath());
		$this->assertSame('foo', (string)$uri);
	}

	public function testRelativePathAndAuhorityIsAutomagicallyFixed() {
		// concatenating a relative path with a host doesn't work: "//example.comfoo" would be wrong
		$uri = (new Uri)->withPath('foo')->withHost('example.com');
		$this->assertSame('/foo', $uri->getPath());
		$this->assertSame('//example.com/foo', (string)$uri);
	}

	public function testPathStartingWithTwoSlashesAndNoAuthorityIsInvalid() {
		self::expectException(\InvalidArgumentException::class);
		self::expectExceptionMessage("The path of a URI without an authority must not start with two slashes \"//\"");
		// URI "//foo" would be interpreted as network reference and thus change the original path to the host
		(new Uri)->withPath('//foo');
	}

	public function testPathStartingWithTwoSlashes() {
		$uri = new Uri('http://example.org//path-not-host.com');
		$this->assertSame('//path-not-host.com', $uri->getPath());
		$uri = $uri->withScheme('');
		$this->assertSame('//example.org//path-not-host.com', (string)$uri); // This is still valid
		self::expectException(\InvalidArgumentException::class);
		$uri->withHost(''); // Now it becomes invalid
	}

	public function testRelativeUriWithPathBeginngWithColonSegmentIsInvalid() {
		self::expectException(\InvalidArgumentException::class);
		self::expectExceptionMessage("A relative URI must not have a path beginning with a segment containing a colon");
		(new Uri)->withPath('mailto:foo');
	}

	public function testRelativeUriWithPathHavingColonSegment() {
		$uri = (new Uri('urn:/mailto:foo'))->withScheme('');
		$this->assertSame('/mailto:foo', $uri->getPath());
		self::expectException(\InvalidArgumentException::class);
		(new Uri('urn:mailto:foo'))->withScheme('');
	}

	public function testDefaultReturnValuesOfGetters() {
		$uri = new Uri();
		$this->assertSame('', $uri->getScheme());
		$this->assertSame('', $uri->getAuthority());
		$this->assertSame('', $uri->getUserInfo());
		$this->assertSame('', $uri->getHost());
		$this->assertNull($uri->getPort());
		$this->assertSame('', $uri->getPath());
		$this->assertSame('', $uri->getQuery());
		$this->assertSame('', $uri->getFragment());
	}

	public function testImmutability() {
		$uri = new Uri();
		$this->assertNotSame($uri, $uri->withScheme('https'));
		$this->assertNotSame($uri, $uri->withUserInfo('user', 'pass'));
		$this->assertNotSame($uri, $uri->withHost('example.com'));
		$this->assertNotSame($uri, $uri->withPort(8080));
		$this->assertNotSame($uri, $uri->withPath('/path/123'));
		$this->assertNotSame($uri, $uri->withQuery('q=abc'));
		$this->assertNotSame($uri, $uri->withFragment('test'));
	}

	public function testGetQueryValue() {
		$uri = new Uri("example.com/test?name=cody&colour=orange");
		self::assertSame("cody", $uri->getQueryValue("name"));
		self::assertSame("orange", $uri->getQueryValue("colour"));
		self::assertNull($uri->getQueryValue("age"));
	}

	public function testConstruct_noPathJustHost():void {
		$uri = new Uri("10.0.0.1");
		self::assertSame("10.0.0.1", $uri->getHost());
	}

	public function testConstruct_pathAndHost():void {
		$uri = new Uri("10.0.0.1/tagbatch");
		self::assertSame("10.0.0.1", $uri->getHost());
		self::assertSame("/tagbatch", $uri->getPath());
	}

	public function testConstruct_pathWithQueryAndHost():void {
		$uri = new Uri("10.0.0.1/tagbatch?id=123");
		self::assertSame("10.0.0.1", $uri->getHost());
		self::assertSame("/tagbatch", $uri->getPath());
		self::assertSame("id=123", $uri->getQuery());
		self::assertSame("123", $uri->getQueryValue("id"));
	}

	public function testConstruct_pathWithQueryAndHostAndPort():void {
		$uri = new Uri("10.0.0.1:4321/tagbatch?id=123");
		self::assertSame("10.0.0.1", $uri->getHost());
		self::assertSame(4321, $uri->getPort());
	}
}
