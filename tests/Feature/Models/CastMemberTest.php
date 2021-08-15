<?php

namespace Tests\Feature\Models;

use App\Models\CastMember;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class CastMemberTest extends TestCase
{
    use DatabaseMigrations;

    public function testCastMemberFields()
    {
        factory(CastMember::class, 1)->create();
        $castMembers = CastMember::all();
        $this->assertCount(1, $castMembers);
        $castMembersKeys = array_keys($castMembers->first()->getAttributes());
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'name',
                'type',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            $castMembersKeys
        );
    }

    public function testCreatingNewCastMember()
    {
        /** @var CastMember $castMember */
        $castMember = CastMember::create(['name' => 'test', 'type' => 1]);
        $castMember->refresh();
        $this->assertTrue(Uuid::isValid($castMember->id));
        $this->assertEquals('test', $castMember->name);
        $this->assertEquals($castMember->type, 1);

        $castMember = CastMember::create(['name' => 'test', 'type' => 2]);
        $this->assertEquals($castMember->type, 2);
    }

    public function testEditingCastMember()
    {
        /** @var CastMember $castMember */
        $castMember = factory(CastMember::class)->create(
            ['type' => 1]
        )->first();
        $data = ['name' => 'test', 'type' => 2];
        $castMember->update($data);

        $this->assertTrue(Uuid::isValid($castMember->id));
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $castMember->{$key});
        }
    }

    public function testExcludingCastMember()
    {
        $castMembers = factory(CastMember::class, 2)->create(
            ['type' => 2]
        );
        $this->assertCount(2, $castMembers);
        /** @var CastMember $castMember */
        $castMember = $castMembers->first();
        $castMember->delete();
        $this->assertCount(2, CastMember::withTrashed()->get());
        $this->assertCount(1, CastMember::all());
        $castMember->restore();
        $this->assertCount(2, CastMember::all());
    }
}
