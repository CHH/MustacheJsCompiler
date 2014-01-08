<?php

namespace MustacheJsCompiler\Test;

use Symfony\Component\Process\ProcessBuilder;

class MustacheJsCompilerTest extends \PHPUnit_Framework_TestCase
{
    protected $compiler;

    function setUp()
    {
        $mustache = new \Phly\Mustache\Mustache;
        $mustache->setTemplatePath(__DIR__ . '/fixtures');

        $this->compiler = new \CHH\MustacheJsCompiler\MustacheJsCompiler($mustache);
        $this->node = ProcessBuilder::create([
            'node', '--use_strict'
        ]);
    }

    function dataProvider()
    {
        return [
            [
                'simple',
                json_encode(['name' => 'John']),
                "Hello John!\n\n"
            ],
            [
               'sections',
                json_encode(['nonlocal' => 'foo', 'person' => ['name' => 'Tim', 'age' => 33], 'users' => [['name' => 'John'], ['name' => 'Jim']]]),
                "Hello John!\nHello Jim!\nfoo\nHi Tim and I'm 33!\nNot Existing!\n\n"
            ],
            [
                'simple',
                '{name: function() { return "John"; }}',
                "Hello John!\n\n"
            ],
            [
                'partials',
                json_encode(['users' => [
                    ['name' => 'Jim', 'age' => 33],
                    ['name' => 'John', 'age' => 24],
                    ['name' => 'Tim', 'age' => 58]
                ]]),
                join("\n\n", [
                    "Hi I'm Jim and I'm 33 years old!",
                    "Hi I'm John and I'm 24 years old!",
                    "Hi I'm Tim and I'm 58 years old!"
                ]) . "\n\n\n"
            ]
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    function test($template, $context, $expected)
    {
        $js = sprintf(
            'var template = %s; console.log(template(%s));',
            $this->compiler->compile($template),
            $context
        );

        $process = $this->node->getProcess();
        $process->setStdin($js);
        $process->run();

        $this->assertNull($process->getErrorOutput());
        $this->assertEquals($expected, $process->getOutput());
    }
}
