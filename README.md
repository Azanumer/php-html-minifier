# PHP HTML Minifier

Dependency-free HTML minifier for PHP 7.4+. Collapses whitespace between tags, strips HTML comments and redundant empty attributes, and lightly minifies inline `<style>` and `<script>` blocks — while leaving whitespace-sensitive content **byte-identical**:

- `<pre>`, `<textarea>`, `<code>` blocks
- IE conditional comments (`<!--[if ...]>`)
- `application/ld+json` JSON-LD blocks

## Usage

```php
require 'src/HtmlMinifier.php';

$minifier = new HtmlMinifier();
echo $minifier->minify($html);
```

As an output buffer (e.g. in `functions.php` or a plugin):

```php
ob_start(function ($buffer) {
    return (new HtmlMinifier())->minify($buffer);
});
```

## Safety notes

- Minification happens per-request and is cheap (single pass + regex), but for best results pair it with a page cache (Nginx FastCGI cache, Redis object cache) so the minified output is generated once.
- Inline `<script>` bodies are only whitespace-collapsed — never re-written — so string literals can't break.
- Empty `alt=""`/`title=""` attributes are stripped; keep them if you rely on them for accessibility selectors.

MIT licensed.
