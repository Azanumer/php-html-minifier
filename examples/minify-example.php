<?php
require __DIR__ . '/../src/HtmlMinifier.php';

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>  Demo page  </title>
    <style>
        /* page styles */
        body   {  color: #333;  margin: 0;  }
        .wrap { padding: 20px; }
    </style>
</head>
<body>
    <!-- this comment will be removed -->
    <div class="wrap">

        <h1>Hello, world</h1>
        <p>Minified    whitespace   here.</p>

        <pre>
    this whitespace
        is preserved
        </pre>

        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Article",
            "headline": "Demo"
        }
        </script>
    </div>
</body>
</html>
HTML;

$minifier = new HtmlMinifier();
echo $minifier->minify( $html ), "\n";
echo "\nOriginal bytes: " . strlen( $html )
	. " | Minified bytes: " . strlen( $minifier->minify( $html ) ) . "\n";
