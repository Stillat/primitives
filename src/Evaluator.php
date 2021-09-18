<?php

namespace Stillat\Primitives;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
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
        } elseif ($node instanceof Array_) {
            return $this->evaluateArray($node);
        } elseif ($node instanceof String_) {
            return $node->value;
        } elseif ($node instanceof UnaryMinus) {
            return -1 * $this->evaluate($node->expr);
        } elseif ($node instanceof FuncCall) {
            return $this->evaluateFunctionCall($node);
        }

        return null;
    }

    protected function evaluateFunctionCall(FuncCall $funcCall)
    {
        $runtimeMethod = new MethodCall();
        $runtimeMethod->name = $funcCall->name->parts[0];

        foreach ($funcCall->args as $arg) {
            if ($arg instanceof Arg) {
                $runtimeMethod->args[] = $this->evaluate($arg->value);
            } else {
                $runtimeMethod->args[] = $this->evaluate($arg);
            }
        }

        return $runtimeMethod;
    }

    private function getConstantValue($value)
    {
        $checkValue = strtolower($value);

        if ($checkValue == 'null') {
            return null;
        } elseif ($checkValue == 'true') {
            return true;
        } elseif ($checkValue == 'false') {
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
