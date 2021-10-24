<?php

namespace Stillat\Primitives;

use Illuminate\Support\Str;
use PhpParser\Lexer\Emulative;
use PhpParser\Node\Arg;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

class Parser
{
    /**
     * @var Php7
     */
    protected $parser;

    /**
     * @var Emulative
     */
    protected $lexer = null;

    public function __construct()
    {
        $this->lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            ],
        ]);
        $this->parser = new Php7($this->lexer);
    }

    /**
     * Parses the string, and returns a list of PHP nodes.
     *
     * @param  string  $string  The string to parse.
     * @return array
     */
    private function getStatements($string)
    {
        $wrapCode = '<?php $primitives = ['.$string.'];';

        return $this->parser->parse($wrapCode)[0]->expr->expr->items;
    }

    /**
     * Parses the input string to produce an array of PHP runtime values.
     *
     * @param  string  $string  The string.
     * @param  array  $context  An optional data context.
     * @return array
     */
    public function parseString($string, $context = [])
    {
        $statements = $this->getStatements($string);

        $values = [];
        $evaluator = new Evaluator();

        foreach ($statements as $statement) {
            $values[] = $evaluator->evaluate($statement->value, $context);
        }

        return $values;
    }

    /**
     * Converts a PHP string into an array of raw PHP expressions.
     *
     * @param  string  $string  The raw string.
     * @return array
     */
    public function safeSplitString($string)
    {
        $statements = $this->getStatements($string);

        $splitValues = [];
        $printer = new Standard();

        foreach ($statements as $statement) {
            $splitValues[] = $printer->prettyPrint([$statement]);
        }

        return $splitValues;
    }

    public function safeSplitNamedString($string)
    {
        $string = '_fakeCall('.$string.')';
        $statements = $this->getStatements($string);
        /** @var Arg[] $args */
        $args = $statements[0]->value->args;

        $splitValues = [];
        $printer = new Standard();

        foreach ($args as $arg) {
            $value = $printer->prettyPrint([$arg->value]);
            $name = null;

            if ($arg->name != null) {
                $name = $arg->name->name;
            }

            $splitValues[] = [
                $value,
                $name,
            ];
        }

        return $splitValues;
    }

    /**
     * Converts the input string into an array containing a method and values.
     *
     * @param  string  $string  The input.
     * @param  array  $context  An optional data context.
     * @return array|null
     */
    public function parseMethod($string, $context = [])
    {
        $string = trim($string);

        if (! Str::endsWith($string, ')')) {
            return $this->parseString($string, $context);
        }

        $parts = explode('(', $string, 2);

        if (count($parts) == 2) {
            $method = $parts[0];
            $args = $this->parseString(mb_substr($parts[1], 0, -1));

            return [
                $method, $args,
            ];
        }

        return null;
    }

    /**
     * Converts the input string into an array of method calls, and values.
     *
     * This method differs from parseMethod() in that it will parse
     * nested method calls, and make instances of MethodCall.
     *
     * @param  string  $string  The input.
     * @param  array  $context  An optional data context.
     * @return array
     */
    public function parseMethods($string, $context = [])
    {
        $statements = $this->getStatements($string);

        $values = [];
        $evaluator = new Evaluator();

        foreach ($statements as $statement) {
            $values[] = $evaluator->evaluate($statement->value, $context);
        }

        return $values;
    }
}
