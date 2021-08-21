<?php

namespace Tests\Stubs;

trait StubsConfiguration
{

    protected function getStubs()
    {
        return [
            [
                'model' => \Tests\Stubs\Models\CategoryStub::class,
                'controller' => \Tests\Stubs\Controllers\CategoryControllerStub::class,
                'create' => ['name' => 'test', 'description' => 'description', 'is_active' => true]
            ],
            [
                'model' => \Tests\Stubs\Models\GenreStub::class,
                'controller' => \Tests\Stubs\Controllers\GenreControllerStub::class,
                'create' => ['name' => 'test', 'is_active' => true]
            ],
            [
                'model' => \Tests\Stubs\Models\CastMemberStub::class,
                'controller' => \Tests\Stubs\Controllers\CastMemberControllerStub::class,
                'create' => ['name' => 'test', 'type' => 1]
            ],
            [
                'model' => \Tests\Stubs\Models\VideoStub::class,
                'controller' => \Tests\Stubs\Controllers\VideoControllerStub::class,
                'create' => [
                    'title' => 'video title',
                    'description' => 'video description',
                    'year_launched' => 2020,
                    'opened' => false,
                    'rating' => 'L',
                    'duration' => 60
                ]
            ]
        ];
    }

}
