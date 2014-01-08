MustacheJsCompiler
==================

Compiles server-side Mustache templates to self-sufficient client-side JS functions. Uses the excellent
[PhlyMustache](http://github.com/phly/phly_mustache).

# Install

    composer require 'chh/mustache-js-compiler':~1.0@dev

# Usage

The compiler needs an instance of `Phly\Mustache\Mustache` to function.

If you intend to share templates between the server and the client,
than it's recommended to use the same Mustache instance which your
application uses so the template paths are setup the same way (for
partials to be compiled correctly).

```php
<?php

$mustache = new Phly\Mustache\Mustache;
// or use the same Mustache environment than your application:
$mustache = $app['mustache'];
```

Then create the compiler and pass it the Mustache environment:

```php
$jsCompiler = new CHH\MustacheJsCompiler\MustacheJsCompiler($mustache);
```

The compiler has a `compile` method which works the same way as `$mustache->tokenize()`. It
looks up the template name in the template path, or uses the passed Mustache code. The `compile`
method returns a self-sufficient JavaScript function which executes the template.

The template looks like this:

```mustache
{{! user/show.mustache }}
Hi {{name}}!
```

We can render the template using this code:

```php
<div id="user-widget"></div>
<script>
  (function() {
    var template = <?php echo $jsCompiler->compile('user/show') ?>;
    var widget = document.getElementById('user-widget');
    widget.innerHTML = template({name: "Christoph"});
  })();
</script>
```

# Unimplemented Mustache Features

* Filters
