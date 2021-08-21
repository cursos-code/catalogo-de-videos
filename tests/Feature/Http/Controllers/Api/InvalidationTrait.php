<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\TestResponse;

trait InvalidationTrait
{

    protected abstract function getValidations($model = null);

    protected function assertValidationRules(string $method, string $rule, array $validations)
    {
        foreach ($validations as $validation) {
            try {
                $field = array_keys($validation)[0];
                $route = $validation[$field]['route'];
                $ruleParams = $validation[$field]['routeParams'] ?? [];
                $params = $validation[$field]['params'];
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }
            $response = $this->withHeaders(['Accept' => 'application/json'])
                ->json(strtoupper($method), $route, $params);
            $this->assertResponseErrorMessages($response, $field, $rule, $ruleParams);
        }
    }

    protected function assertResponseErrorMessages(
        TestResponse $response,
        string $field,
        string $rule,
        array $params = []
    ) {
        $response->assertJsonValidationErrors([$field]);
        $field = str_replace('_', ' ', $field);
        $response->assertJsonFragment(
            [
                \Lang::get("validation.{$rule}", ['attribute' => $field] + $params)
            ]
        );
    }

}
