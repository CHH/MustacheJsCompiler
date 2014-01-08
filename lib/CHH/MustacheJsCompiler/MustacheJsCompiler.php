<?php

namespace CHH\MustacheJsCompiler;

use Phly\Mustache\Mustache;
use Phly\Mustache\Lexer;

/**
 * Compiles a server-side Mustache template to a JavaScript function
 * for client-side use.
 *
 * The resulting JS function can be used by passing an object as first
 * argument and returns the rendered template, e.g.
 *
 *     <?php
 *         $mustache = new Phly\Mustache\Mustache;
 *         $compiler = new CHH\MustacheJsCompiler\MustacheJsCompiler($mustache);
 *     ?>
 *     <div id="some-widget"></div>
 *     <script>
 *         var showUserTemplate = <?php echo $compiler->compile('users/show') ?>;
 *         var widget = document.getElementById('some-widget');
 *         widget.innerHTML = showUserTemplate({name: "John Doe"});
 *     </script>
 *
 * The generated JS is completely self-sufficient and thus doesn't need any
 * libraries to work. This means a small amount of utilities is included in every function,
 * a good JavaScript optimizer should be able to take care of the duplication though.
 */
class MustacheJsCompiler
{
    private $mustache;

    function __construct(Mustache $mustache)
    {
        $this->mustache = $mustache;
    }

    /**
     * Compile the template to JS code.
     *
     * @param string $template Template string or file name
     * @return string Template in JavaScript code
     */
    function compile($template)
    {
        $tokens = $this->mustache->tokenize($template);

        $js = 'function(context) { var stack = [], buf = ""; stack.push(context);';
        $js .= $this->getUtilityFunctions();
        $js .= $this->getJsCode($tokens);
        $js .= 'return buf;}';

        return $js;
    }

    /**
     * @todo remove whitespace in JS code
     */
    private function getJsCode(array $tokens)
    {
        $js = "";

        foreach ($tokens as $token) {
            switch ($token[0]) {
            case Lexer::TOKEN_CONTENT:
                $js .= sprintf('buf += %s;', json_encode($token[1]));
                break;
            case Lexer::TOKEN_VARIABLE:
                $variable = $token[1];
                if ($token[1] === '.') {
                    $variable = '__it';
                }
                $js .= sprintf('buf += __escape(__get(stack, "%s"));', $variable);
                break;
            case Lexer::TOKEN_VARIABLE_RAW;
                if ($token[1] === '.') {
                    $variable = '__it';
                }
                $js .= sprintf('buf += __get(stack, "%s");', $variable);
                break;
            case Lexer::TOKEN_SECTION:
                $js .= $this->renderSection($token);
                break;
            case Lexer::TOKEN_SECTION_INVERT:
                $js .= "if(!__get(stack, '{$token[1]['name']}')) {";
                $js .= $this->getJsCode($token[1]['content']);
                $js .= '}'; // end if
                break;
            case Lexer::TOKEN_PARTIAL:
                $js .= $this->getJsCode($token[1]['tokens']);
                break;
            }
        }

        return $js;
    }

    private function renderSection($token)
    {
        $section = <<<EOF
(function() {
    var section = __get(stack, '{$token[1]['name']}'),
        code = function(stack) { {$this->getJsCode($token[1]['content'])} };
    if (section) {
        if (__isArray(section)) {
            __each(section, function(__val, __it) {
                stack.push(__val);
                if (typeof stack[stack.length-1] === 'object') {
                    stack[stack.length-1]['__it'] = __it;
                }
                code(stack);
                stack.pop();
            });
        } else {
            stack.push(section)
            code(stack);
            stack.pop();
        }
    }
})();
EOF;

        return $section;
    }

    private function getUtilityFunctions()
    {
        // From github.com/janl/mustache.js
        $escapeFn = <<<EOF
var entityMap = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': '&quot;',
    "'": '&#39;',
    "/": '&#x2F;'
};

function __escape(string) {
    return String(string).replace(/[&<>"'\/]/g, function (s) {
        return entityMap[s];
    });
}
EOF;

        // Walks the stack backwards until the property is found
        $getFn = <<<EOF
var __get = function(stack, prop) {
    for (var i = stack.length - 1; i >= 0; i--) {
        if (stack[i][prop] !== undefined) {
            if (typeof stack[i][prop] === 'function') return stack[i][prop].call(stack[i]);
            return stack[i][prop];
        }
    }
};
EOF;

        // From github.com/janl/mustache.js
        $isArrayFn = <<<EOF
var __isArray = Array.isArray || function (object) {
    return Object.prototype.toString.call(object) === '[object Array]';
};
EOF;

        // From underscore.js
        $eachFn = <<<EOF
var __keys = Object.keys || function(obj) {
    if (obj !== Object(obj)) throw new TypeError('Invalid object');
    var keys = [];
    for (var key in obj) if (Object.prototype.hasOwnProperty.call(obj, key)) keys.push(key);
    return keys;
};
var __each = function(obj, iterator, context) {
    var nativeForEach = Array.prototype.forEach;
    if (obj == null) return;
    if (nativeForEach && obj.forEach === nativeForEach) {
        obj.forEach(iterator, context);
    } else if (obj.length === +obj.length) {
        for (var i = 0, length = obj.length; i < length; i++) {
            if (iterator.call(context, obj[i], i, obj) === breaker) return;
        }
    } else {
        var keys = __keys(obj);
        for (var i = 0, length = keys.length; i < length; i++) {
            if (iterator.call(context, obj[keys[i]], keys[i], obj) === breaker) return;
        }
    }
};
EOF;

        return join("\n", array($escapeFn, $getFn, $isArrayFn, $eachFn));
    }
}
