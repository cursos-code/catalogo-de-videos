<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\TestResponse;

trait InvalidationTrait
{
    private function assertInvalidationRequired(TestResponse $response, $field)
    {
        $response->assertJsonValidationErrors([$field])
            ->assertJsonFragment(
                [
                    \Lang::get('validation.required', ['attribute' => $field])
                ]
            );
    }

    private function assertInvalidationMax(TestResponse $response)
    {
    }
}
