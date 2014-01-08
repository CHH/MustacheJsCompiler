<?php

require __DIR__ . '/../vendor/autoload.php';

$mustache = new Phly\Mustache\Mustache;
$mustache->setTemplatePath(dirname($argv[1]));
$tokens = $mustache->getLexer()->compile(file_get_contents($argv[1]), $argv[1]);

$rc = new ReflectionClass(\Phly\Mustache\Lexer::class);

$tokenLookup = [];
foreach ($rc->getConstants() as $const => $value) {
    if (substr($const, 0, strlen('TOKEN_')) === 'TOKEN_') {
        // Later we look up by token value, let's index by it for faster lookups
        $tokenLookup[$value] = $const;
    }
}

$tokens = array_map(
    function($token) use ($tokenLookup) {
        $token[0] = $tokenLookup[$token[0]];
        return $token;
    },
    $tokens
);

$tokens = array_map(
    function($token) {
        return $token;
    },
    $tokens
);

foreach ($tokens as $token) {
    print_r($token);
    //printf("%s\t%s\n", $token[0], join("\t", array_slice($token, 1)));
}
