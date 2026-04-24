<?php
namespace GT\Http;

/**
 * Resolves a URI reference in the context of a base URI and the opposite way.
 *
 * @author Tobias Schultze
 *
 * @link https://tools.ietf.org/html/rfc3986#section-5
 */
class UriResolver {
	/**
	 * Removes dot segments from a path and returns the new path.
	 * @link http://tools.ietf.org/html/rfc3986#section-5.2.4
	 * @SuppressWarnings("CyclomaticComplexity") // TODO: Refactor one day :)
	 */
	public function removeDotSegments(string $path):string {
		if($path === "" || $path === "/") {
			return $path;
		}

		$results = [];
		$segments = explode("/", $path);

		$segment = null;
		foreach($segments as $segment) {
			if($segment === "..") {
				array_pop($results);
			}
			elseif($segment !== ".") {
				$results[] = $segment;
			}
		}

		$newPath = implode("/", $results);

		if($path[0] === "/" && (!isset($newPath[0]) || $newPath[0] !== "/")) {
// Re-add the leading slash if necessary for cases like "/.."
			$newPath = "/" . $newPath;
		}
		elseif($newPath !== "" && ($segment === "." || $segment === "..")) {
// Add the trailing slash if necessary
// If newPath is not empty, then $segment must be set and is the last segment
// from the foreach
			$newPath .= "/";
		}

		return $newPath;
	}

	/**
	 * Converts the relative URI into a new URI that is resolved against
	 * the base URI.
	 * @link http://tools.ietf.org/html/rfc3986#section-5.2
	 * @SuppressWarnings("CyclomaticComplexity") // TODO: Refactor one day :)
	 */
	public function resolve(Uri $base, Uri $rel):Uri {
		if((string)$rel === "") {
// We can return the same base URI instance for this same-document reference.
			return $base;
		}

		if($rel->getScheme() !== "") {
			return $rel->withPath($this->removeDotSegments($rel->getPath()));
		}

		if($rel->getAuthority() !== "") {
			$targetAuthority = $rel->getAuthority();
			$targetPath = $this->removeDotSegments($rel->getPath());
			$targetQuery = $rel->getQuery();
		}
		else {
			$targetAuthority = $base->getAuthority();
			if($rel->getPath() === "") {
				$targetPath = $base->getPath();
				$targetQuery = $rel->getQuery() !== "" ? $rel->getQuery()
					: $base->getQuery();
			}
			else {
				if($rel->getPath()[0] === "/") {
					$targetPath = $rel->getPath();
				}
				else {
					if($targetAuthority !== "" && $base->getPath() === "") {
						$targetPath = "/" . $rel->getPath();
					}
					else {
// TODO: Hotspot for refactoring opportunity.
						$lastSlashPos = strrpos($base->getPath(), "/");
						if($lastSlashPos === false) {
							$targetPath = $rel->getPath();
						}
						else {
							$targetPath = substr(
								$base->getPath(),
								0,
								$lastSlashPos + 1
							) . $rel->getPath();
						}
					}
				}
				$targetPath = $this->removeDotSegments($targetPath);
				$targetQuery = $rel->getQuery();
			}
		}

		$uriFactory = new UriFactory();
		return $uriFactory->composeFromComponents(
			$base->getScheme(),
			$targetAuthority,
			$targetPath,
			$targetQuery,
			$rel->getFragment()
		);
	}
}
