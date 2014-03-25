php-effective-css
=================

Given a DOMDocument and a CSS file, calculate the effective CSS for each node in the DOM

Usage:

    $parser = new PHPEffectiveCss;
    $parser->apply_css($dom, $css);

How it works
=================
For each selector in the CSS, it finds the matching DOM nodes and map the selector/styles to the node.
Then for each node it gets all selectors applied and calculates the weight of the selector to determine which properties to override.
The resulting CSS is applied as a style attribute to the node

This is a very early release.


TODO
=================
* ~~Write better css-weight algoritm~~ 
* ~~Handle !important declarations~~
* Get into account inline styles
* Allow querying for the effective CSS by selector, instead of inlining the css
* Handle inherit styles
* Write tests

dependencies
=================
[php-selector](https://github.com/visionmedia/php-selector)

[PHP-CSS-Parser](https://github.com/sabberworm/PHP-CSS-Parser)
