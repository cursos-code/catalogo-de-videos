<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\TestResponse;

trait StoreTrait
{

    protected abstract function getModel();

    protected abstract function getStructure(): array;

    protected function assertStore($route, array $sendData, array $testDatabase): TestResponse
    {
        /** @var TestResponse $response */
        $response = $this->withHeaders(['Accept' => 'application/json'])->post($route, $sendData);
        if ($response->status() !== 201) {
            throw new \Exception("expect 201, given {$response->status()}\n content: {$response->content()}");
        }
        $response->assertStatus(201);
        $this->assertInDatabase($response, $testDatabase);
        $this->assertInJson($response, $testDatabase);
        $this->assertInStructure($response, $this->getStructure());
        return $response;
    }

    protected function assertUpdate($route, array $sendData, array $testDatabase): TestResponse
    {
        /** @var TestResponse $response */
        $response = $this->withHeaders(['Accept' => 'application/json'])->put($route, $sendData);
        if ($response->status() !== 200) {
            throw new \Exception("expect 200, given {$response->status()}\n content: {$response->content()}");
        }
        $response->assertStatus(200);
        $this->assertInDatabase($response, $testDatabase);
        $this->assertInJson($response, $testDatabase);
        $this->assertInStructure($response, $this->getStructure());
        return $response;
    }

    protected function assertDateByRegex(TestResponse $response, array $testRegex)
    {
        $regex = '/^[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2}$/';
        foreach ($testRegex as $test) {
            $this->assertRegExp($regex, $response->json($test));
        }
    }

    private function assertInDatabase(TestResponse $response, array $testDatabase)
    {
        $model = $this->getModel();
        $this->assertDatabaseHas((new $model)->getTable(), $testDatabase + ['id' => $response->json(['id'])]);
    }

    private function assertInJson(TestResponse $response, array $testDatabase)
    {
        $response->assertJsonFragment($testDatabase + ['id' => $response->json(['id'])]);
    }

    private function assertInStructure(TestResponse $response, array $testStructure)
    {
        $response->assertJsonStructure($testStructure);
    }

}
