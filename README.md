MustacheJsCompiler
==================

Compiles server-side Mustache templates to self-sufficient client-side JS functions. Uses the excellent
[PhlyMustache](http://github.com/phly/phly_mustache).

# Install

  composer require 'chh/mustache-js-compiler':~1.0@dev

# Usage

First you need a Mustache environment. You could take this for example already by your application
for easy template sharing between server and client:

```php
<?php

$mustache = new Phly\Mustache\Mustache;
// or
$mustache = $app['mustache'];
```

Then create the compiler and pass it the Mustache environment:

```php
$jsCompiler = new CHH\MustacheJsCompiler\MustacheJsCompiler($mustache);
```

The compiler has a `compile` method which has works the same way as `$mustache->tokenize()`. It
looks up the template name in the template path, or uses the passed Mustache code. The `compile`
method returns a self-sufficient JavaScript function which executes the template.

The template looks like this:

```mustache
{{! user/show.mustache }}
Hi {{name}}!
```

```php
<div id="user-widget"></div>
<script>
  (function() {
    var template = <?php echo $compiler->compile('user/show') ?>
    var widget = document.getElementById('user-widget');
    widget.innerHTML = template({name: "Christoph"});
  })();
</script>
```

# Unimplemented Mustache Features

* Partials
* Filters
