<?php

namespace Stillat\Primitives;

class MethodRunner
{
    /**
     * Invokes nested methods on the provided object target.
     *
     * @param  array  $runtimeArgs  The runtime arguments.
     * @param  object  $target  The object to invoke methods on.
     * @return false|mixed|null
     */
    public function run($runtimeArgs, $target)
    {
        if (empty($runtimeArgs) || ! is_object($target)) {
            return null;
        }

        if (count($runtimeArgs) > 1 || ! $runtimeArgs[0] instanceof MethodCall) {
            return null;
        }

        return $this->getTargetResult($runtimeArgs[0], $target);
    }

    /**
     * Invokes the provided method on the target object instance.
     *
     * @param  MethodCall  $methodCall  The method to invoke.
     * @param  object  $target  The object to invoke the method on.
     * @return false|mixed
     */
    protected function getTargetResult(MethodCall $methodCall, $target)
    {
        $runtimeArgs = [];

        foreach ($methodCall->args as $arg) {
            if ($arg instanceof MethodCall) {
                $runtimeArgs[] = $this->getTargetResult($arg, $target);
            } else {
                $runtimeArgs[] = $arg;
            }
        }

        return call_user_func([$target, $methodCall->name], ...$runtimeArgs);
    }
}
