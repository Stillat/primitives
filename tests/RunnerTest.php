<?php

use PHPUnit\Framework\TestCase;
use Stillat\Primitives\MethodRunner;
use Stillat\Primitives\Parser;

class RunnerTest extends TestCase
{
    protected $parser = null;
    protected $methodRunner = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->parser = new Parser();
        $this->methodRunner = new MethodRunner();
    }

    public function test_it_calls_methods_on_class_instances()
    {
        $parseResults = $this->parser
            ->parseMethods("randomElements(['a', 'b', 'c', 'd', 'e'], rand(1, 5))");

        $myClassInstance = new TestTarget();

        $runResults = $this->methodRunner->run($parseResults, $myClassInstance);

        $this->assertSame('Input: abcde : rand: min 1 max: 5', $runResults);
    }

    public function test_unmatched_constants_are_converted_to_method_calls()
    {
        $parseResults = $this->parser->parseMethods('say(lastName)');

        $myClassInstance = new TestTarget();
        $runResults = $this->methodRunner->run($parseResults, $myClassInstance);

        $this->assertSame('Said: I am the last name.', $runResults);
    }

}

class TestTarget
{
    public function rand($min, $max)
    {
        return 'rand: min '.$min.' max: '.$max;
    }

    public function randomElements($array, $limit)
    {
        $input = implode('', $array);

        return 'Input: '.$input.' : '.$limit;
    }

    public function lastName()
    {
        return 'I am the last name.';
    }

    public function say($text)
    {
        return 'Said: '.$text;
    }
}
