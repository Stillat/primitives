<?php

namespace Stillat\Primitives;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;

class Evaluator
{

    public function evaluate($node)
    {
        if ($node instanceof LNumber) {
            return intval($node->value);
        } elseif ($node instanceof DNumber) {
            return floatval($node->value);
        } elseif ($node instanceof ConstFetch) {
            return $this->getConstantValue($node->name->parts[0]);
        } else if ($node instanceof Array_) {
            return $this->evaluateArray($node);
        } else if ($node instanceof String_) {
            return $node->value;
        } else if ($node instanceof UnaryMinus) {
            return -1 * $this->evaluate($node->expr);
        }

        return null;
    }

    private function getConstantValue($value)
    {
        $checkValue = strtolower($value);

        if ($checkValue == 'null') {
            return null;
        } else if ($checkValue == 'true') {
            return true;
        } else if ($checkValue == 'false') {
            return false;
        }

        // Account for things like M_PI, etc.
        if (defined($value)) {
            return constant($value);
        }

        return null;
    }

    protected function evaluateArray(Array_ $array)
    {
        $arrayValues = [];

        foreach ($array->items as $item) {
            if ($item->key == null) {
                $arrayValues[] = $this->evaluate($item->value);
            } else {
                $arrayValues[$this->evaluate($item->key)] = $this->evaluate($item->value);
            }
        }

        return $arrayValues;
    }

}