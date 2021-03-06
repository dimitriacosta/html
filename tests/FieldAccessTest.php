<?php

namespace Styde\Html\Tests;

use Styde\Html\Facades\Field;
use Styde\Html\Fields\FieldBuilder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Support\Htmlable;

class FieldAccessTest extends TestCase
{
    /** @test */
    function it_only_renders_the_field_if_the_user_has_the_expected_role()
    {
        $field = Field::text('name');

        $this->assertDontRender($field->ifIs('admin'));

        $this->actingAs($this->aUser())
            ->assertDontRender($field->ifIs('admin'));

        $this->actingAs($this->anAdmin())
            ->assertRender($field->ifIs('admin'));
    }

    /** @test */
    function it_only_renders_the_field_if_the_user_is_not_guest()
    {
        $field = Field::text('name');

        $this->assertRender($field->ifGuest());

        $this->actingAs($this->aUser())
            ->assertDontRender($field->ifGuest());
    }

    /** @test */
    function if_only_renders_the_field_if_user_is_logged_in()
    {
        $field = Field::text('name');

        $this->assertDontRender($field->ifAuth());

        $this->actingAs($this->aUser())
            ->assertRender($field->ifAuth());
    }

    /** @test */
    function it_only_renders_the_field_if_the_user_has_the_given_ability()
    {
        $this->actingAs($this->aUser());

        Gate::define('edit-all', function ($user) {
            return false;
        });

        Gate::define('edit-mine', function ($user) {
            return true;
        });

        $field = Field::text('name');

        $this->assertDontRender($field->ifCan('edit-all'));

        $this->assertRender($field->ifCan('edit-mine'));
    }

    /** @test */
    function it_only_renders_the_field_if_the_user_does_not_have_the_given_ability()
    {
        $this->actingAs($this->aUser());

        Gate::define('edit-all', function ($user) {
            return false;
        });

        Gate::define('edit-mine', function ($user) {
            return true;
        });

        $field = Field::text('name');

        $this->assertRender($field->ifCannot('edit-all'));

        $this->assertDontRender($field->ifCannot('edit-mine'));
    }

    protected function assertRender(FieldBuilder $field)
    {
        $this->assertInstanceOf(Htmlable::class, $field);
        $this->assertNotEmpty((string) $field->render());
    }

    protected function assertDontRender(FieldBuilder $field)
    {
        $this->assertSame('', $field->render());
    }
}
