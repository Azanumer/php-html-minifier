<?php
/**
 * Safe, dependency-free HTML minifier for PHP.
 *
 * Collapses whitespace between tags, strips HTML comments and redundant
 * attributes, and minifies whitespace inside <style> and <script> blocks —
 * while leaving <pre>, <textarea>, <code>, conditional comments, and
 * JSON-LD blocks byte-identical.
 *
 * Usage:
 *   $min = new HtmlMinifier();
 *   echo $min->minify($html);
 *
 *   // Or as an output buffer:
 *   ob_start(function ($buffer) { return (new HtmlMinifier())->minify($buffer); });
 */

class HtmlMinifier {

	/** @var array Placeholder store for protected blocks. */
	private $placeholders = array();

	/** @var int Counter for placeholder keys. */
	private $counter = 0;

	/**
	 * Minify an HTML string.
	 *
	 * @param string $html Full HTML document or fragment.
	 * @return string Minified HTML.
	 */
	public function minify( $html ) {
		$this->placeholders = array();
		$this->counter      = 0;

		// 1. Protect blocks whose whitespace is significant.
		$html = $this->protect( $html, '/<(pre|textarea|code)\b[^>]*>.*?<\/\1>/si' );
		// 2. Protect conditional comments (IE).
		$html = $this->protect( $html, '/<!--\[(?:if|endif)[^\]]*?\]>.*?<!\[endif\]-->/si' );
		// 3. Protect JSON-LD blocks (whitespace sometimes matters to validators).
		$html = $this->protect( $html, '/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>/si' );
		// 4. Protect regular <script> and <style> contents (minified lightly, separately).
		$html = $this->protect( $html, '/<script\b(?![^>]*type=["\']application\/ld\+json["\'])[^>]*>.*?<\/script>/si', 'js' );
		$html = $this->protect( $html, '/<style\b[^>]*>.*?<\/style>/si', 'css' );

		// 5. Strip normal HTML comments (not <!--[if ...]-->, already protected).
		$html = preg_replace( '/<!--(?!<!)[^\[>].*?-->/s', '', $html );

		// 6. Collapse whitespace between tags.
		$html = preg_replace( '/>\s+</s', '><', $html );

		// 7. Collapse runs of whitespace to a single space.
		$html = preg_replace( '/[ \t\r\n\f\v]{2,}/', ' ', $html );

		// 8. Remove redundant empty attributes commonly left by builders.
		$html = preg_replace( '/\s+(?:alt|title)=""/', '', $html );

		// 9. Trim spaces inside tag brackets: <div  class> -> <div class>.
		$html = preg_replace_callback(
			'/<[^>]+>/',
			function ( $m ) {
				return preg_replace( '/\s{2,}/', ' ', $m[0] );
			},
			$html
		);

		$html = trim( $html );

		// 10. Restore protected blocks.
		return $this->restore( $html );
	}

	/**
	 * Replace matches of $pattern with placeholders.
	 *
	 * @param string $html    Input.
	 * @param string $pattern PCRE pattern.
	 * @param string $kind    '' (verbatim), 'js', or 'css' (light minification).
	 * @return string
	 */
	private function protect( $html, $pattern, $kind = '' ) {
		return preg_replace_callback(
			$pattern,
			function ( $m ) use ( $kind ) {
				$key = "\x00MIN" . ( $this->counter++ ) . "\x00";
				$val = $m[0];
				if ( $kind === 'js' ) {
					$val = $this->lightMinifyJs( $val );
				} elseif ( $kind === 'css' ) {
					$val = $this->lightMinifyCss( $val );
				}
				$this->placeholders[ $key ] = $val;
				return $key;
			},
			$html
		);
	}

	/**
	 * Restore placeholders to their original content.
	 *
	 * @param string $html Input.
	 * @return string
	 */
	private function restore( $html ) {
		return strtr( $html, $this->placeholders );
	}

	/**
	 * Lightly minify the contents of a <script> block: collapse indentation
	 * and blank lines without touching string literals.
	 *
	 * @param string $block Full <script>...</script> block.
	 * @return string
	 */
	private function lightMinifyJs( $block ) {
		// Split tag and body.
		if ( ! preg_match( '/^(<script\b[^>]*>)(.*?)(<\/script>)$/si', $block, $m ) ) {
			return $block;
		}
		$body = preg_replace( '/[ \t]*\r?\n[ \t]*/', "\n", $m[2] );
		$body = preg_replace( '/\n{2,}/', "\n", $body );
		$body = trim( $body );
		return $m[1] . $body . $m[3];
	}

	/**
	 * Lightly minify the contents of a <style> block.
	 *
	 * @param string $block Full <style>...</style> block.
	 * @return string
	 */
	private function lightMinifyCss( $block ) {
		if ( ! preg_match( '/^(<style\b[^>]*>)(.*?)(<\/style>)$/si', $block, $m ) ) {
			return $block;
		}
		$body = $m[2];
		$body = preg_replace( '~/\*.*?\*/~s', '', $body );   // strip CSS comments
		$body = preg_replace( '/\s*([{}:;,>~+])\s*/', '$1', $body );
		$body = preg_replace( '/;}/', '}', $body );
		$body = trim( $body );
		return $m[1] . $body . $m[3];
	}
}
