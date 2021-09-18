<?php

namespace Stillat\Primitives;

use PhpParser\Lexer\Emulative;
use PhpParser\Parser\Php7;
use Stillat\Primitives\Utilities\StringUtilities;

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
     * Parses the input string to produce an array of PHP runtime values.
     * 
     * @param string $string The string.
     * @return array
     */
    public function parseString($string)
    {
        $wrapCode = '<?php $primitives = ['.$string.'];';
        $statements = $this->parser->parse($wrapCode)[0]->expr->expr->items;

        $values = [];
        $evaluator = new Evaluator();

        foreach ($statements as $statement) {
            $values[] = $evaluator->evaluate($statement->value);
        }

        return $values;
    }

    /**
     * Converts the input string into an array containing a method and values.
     *
     * @param string $string The input.
     * @return array|null
     */
    public function parseMethod($string)
    {
        $string = trim($string);

        if (!StringUtilities::endsWith($string, ')')) {
            return $this->parseString($string);
        }

        $parts = explode('(', $string, 2);

        if (count($parts) == 2) {
            $method = $parts[0];
            $args = $this->parseString(mb_substr($parts[1], 0, -1));

            return [
                $method, $args
            ];
        }

        return null;
    }

}