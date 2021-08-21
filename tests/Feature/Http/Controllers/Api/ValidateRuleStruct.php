<?php

namespace Tests\Feature\Http\Controllers\Api;

trait ValidateRuleStruct
{

    public abstract function getStruct();

    public function assertValidationStructRules($class, $method = 'getRules')
    {
        $reflectionMethod = new \ReflectionMethod($class, $method);
        $reflectionMethod->setAccessible(true);
        $this->assertEqualsCanonicalizing($reflectionMethod->invoke(new $class()), $this->getStruct());
    }

}
