<?php

namespace Stillat\Primitives;

use Illuminate\Support\Str;
use PhpParser\Lexer\Emulative;
use PhpParser\Parser\Php7;

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
     * @param array $context An optional data context.
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
     * Converts the input string into an array containing a method and values.
     *
     * @param  string  $string  The input.
     * @param array $context An optional data context.
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
     * @param string $string The input.
     * @param array $context An optional data context.
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
