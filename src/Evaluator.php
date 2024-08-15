<?php

namespace Stillat\Primitives;

use Illuminate\Support\Arr;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;

class Evaluator
{
    public function evaluate($node, $context = [])
    {
        if ($node instanceof LNumber) {
            return intval($node->value);
        } elseif ($node instanceof DNumber) {
            return floatval($node->value);
        } elseif ($node instanceof ConstFetch) {
            return $this->getConstantValue($node->name->getParts()[0]);
        } elseif ($node instanceof Array_) {
            return $this->evaluateArray($node, $context);
        } elseif ($node instanceof String_) {
            return $node->value;
        } elseif ($node instanceof UnaryMinus) {
            return -1 * $this->evaluate($node->expr, $context);
        } elseif ($node instanceof FuncCall) {
            return $this->evaluateFunctionCall($node, $context);
        } elseif ($node instanceof Variable) {
            return Arr::get($context, $node->name, null);
        } elseif ($node instanceof PropertyFetch) {
            $path = $this->getNestedPath($node);

            return Arr::get($context, $path, null);
        }

        return null;
    }

    protected function getNestedPath(PropertyFetch $propertyFetch)
    {
        $nodeToCheck = $propertyFetch;
        $parts = [];

        while ($nodeToCheck instanceof PropertyFetch) {
            $parts[] = $nodeToCheck->name->name;

            $nodeToCheck = $nodeToCheck->var;
        }

        $parts[] = $nodeToCheck->name;

        return implode('.', array_reverse($parts));
    }

    protected function evaluateFunctionCall(FuncCall $funcCall, $context)
    {
        $runtimeMethod = new MethodCall();
        $runtimeMethod->name = $funcCall->name->getParts()[0];

        foreach ($funcCall->args as $arg) {
            if ($arg instanceof Arg) {
                $runtimeMethod->args[] = $this->evaluate($arg->value, $context);
            } else {
                $runtimeMethod->args[] = $this->evaluate($arg, $context);
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

        // Convert unknown constants into method calls.
        $runtimeMethod = new MethodCall();
        $runtimeMethod->name = $value;
        $runtimeMethod->args = [];

        return $runtimeMethod;
    }

    protected function evaluateArray(Array_ $array, $context)
    {
        $arrayValues = [];

        foreach ($array->items as $item) {
            if ($item->key == null) {
                $arrayValues[] = $this->evaluate($item->value, $context);
            } else {
                $arrayValues[$this->evaluate($item->key, $context)] = $this->evaluate($item->value, $context);
            }
        }

        return $arrayValues;
    }
}
