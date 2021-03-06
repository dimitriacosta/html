<?php

namespace Styde\Html\Tests;

use Illuminate\Support\Facades\View;
use Styde\Html\Fields\Field;
use Styde\Html\FormModel;
use Styde\Html\Fields\FieldBuilder;
use Styde\Html\FormModel\FieldCollection;

class FieldCollectionTest extends TestCase
{
    /** @test */
    function check_if_the_field_collection_is_empty()
    {
        $form = app(TestFormModel::class);

        $this->assertTrue($form->fields->isEmpty());

        $form->text('first_name');

        $this->assertFalse($form->fields->isEmpty());
    }

    /** @test */
    function it_adds_and_gets_a_field()
    {
        $form = app(TestFormModel::class);

        $form->text('first_name');

        $this->assertInstanceOf(FieldBuilder::class, $form->fields->first_name);
    }
    
    /** @test */
    function it_adds_a_number_field()
    {
        $form = app(TestFormModel::class);

        $form->number('distance');

        $this->assertFieldTypeIs('number', $form->distance);
    }

    /** @test */
    function it_adds_a_textarea_field()
    {
        $form = app(TestFormModel::class);

        $form->textarea('content');

        $this->assertFieldTypeIs('textarea', $form->content);
    }

    /** @test */
    function it_adds_an_url_field()
    {
        $form = app(TestFormModel::class);

        $form->url('website');

        $this->assertFieldTypeIs('url', $form->website);
    }

    /** @test */
    function it_adds_a_select_field_with_options()
    {
        $form = app(TestFormModel::class);

        $fieldBuilder = $form->select('role', ['admin' => 'Admin' , 'user' => 'User']);

        $this->assertFieldTypeIs('select', $form->role);

        $this->assertEquals($fieldBuilder->getField()->getOptions(), ['admin' => 'Admin' , 'user' => 'User']);
    }
    
    /** @test */
    function it_adds_a_radios_field_with_options()
    {
        $form = app(TestFormModel::class);

        $fieldBuilder = $form->radios('gender', ['m' => 'Male', 'f' => 'Female']);

        $this->assertFieldTypeIs('radios', $form->gender);

        $this->assertEquals($fieldBuilder->getField()->getOptions(), ['m' => 'Male', 'f' => 'Female']);
    }

    /** @test */
    function it_adds_a_checkboxes_field_with_options()
    {
        $form = app(TestFormModel::class);

        $options = [
            'php' => 'PHP',
            'js' => 'JS',
            'css' => 'CSS'
        ];

        $fieldBuilder = $form->checkboxes('tags', $options);

        $this->assertEquals($fieldBuilder->getField()->getOptions(), $options);

        $this->assertFieldTypeIs('checkboxes', $form->tags);
    }

    /** @test */
    function it_adds_an_integer_field()
    {
        $form = app(TestFormModel::class);

        $form->integer('pin');

        $this->assertFieldTypeIs('integer', $form->pin);
    }
    
    /** @test */
    function it_adds_a_file()
    {
        $form = app(TestFormModel::class);

        $form->file('document');

        $this->assertFieldTypeIs('file', $form->document);

        $this->assertSame('multipart/form-data', $form->form->enctype);
    }

    /** @test */
    function it_adds_a_hidden_field()
    {
        $form = app(TestFormModel::class);
        $form->hidden('plan');

        $this->assertFieldTypeIs('hidden', $form->plan);
    }

    /** @test */
    function hidden_fields_are_rendered_as_control_only_by_default()
    {
        $form = app(TestFormModel::class);
        $form->hidden('plan');

        $this->assertHtmlEquals('<input type="hidden" name="plan">', $form->plan);
    }

    /** @test */
    function passes_custom_variables_to_the_field_template()
    {
        View::addLocation(__DIR__.'/views');

        $form = app(TestFormModel::class);

        $form->text('custom')
            ->template('@fields.extra')
            ->with('customVar', 'custom value');

        $this->assertHtmlEquals(
            '<strong>custom value</strong>',
            $form->custom
        );
    }

    /** @test */
    function passes_custom_variables_to_the_field_template_method()
    {
        View::addLocation(__DIR__.'/views');

        $form = app(TestFormModel::class);

        $form->text('custom')
            ->template('@fields.extra', [
                'customVar' => 'custom value'
            ]);

        $this->assertHtmlEquals(
            '<strong>custom value</strong>',
            $form->custom
        );
    }

    public function assertFieldTypeIs($type, $field)
    {
        if ($field instanceof FieldBuilder) {
            $field = $field->getField();
        }

        $this->assertInstanceOf(Field::class, $field);
        $this->assertSame($type, $field->type);
    }

    /** @test */
    function it_renders_the_field_collection()
    {
        $form = app(TestFormModel::class);

        $form->text('name')->label('Full name');
        $form->select('role')->options(['admin' => 'Admin' , 'user' => 'User']);

        $this->assertTemplateMatches('field-collection/fields', $form->renderFields());
    }

    /** @test */
    function it_can_add_basic_html_tags()
    {
        $form = app(TestFormModel::class);

        $tag = $form->tag('h3', 'Title', ['class' => 'red']);

        $this->assertHtmlEquals('<h3 class="red">Title</h3>', $tag);

        $tag = $form->tag('hr', ['class' => 'red']);

        $this->assertHtmlEquals('<hr class="red">', $tag);

        $this->assertCount(2, $form->fields->all());
    }
}
