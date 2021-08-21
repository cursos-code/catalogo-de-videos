<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BasicCrudController;
use App\Models\CastMember;

class CastMemberController extends BasicCrudController
{

    protected function getModel()
    {
        return CastMember::class;
    }

    protected function getRules()
    {
        return [
            'name' => 'required|max:255',
            'type' => 'required|integer|in:'.implode(',', [CastMember::TYPE_ACTOR, CastMember::TYPE_DIRECTOR])
        ];
    }

    protected function getUpdateRules()
    {
        return $this->getRules();
    }
}
