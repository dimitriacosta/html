<?php

namespace Styde\Html;

use BadMethodCallException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Styde\Html\Facades\Html;
use Styde\Html\FormModel\Field;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\Macroable;
use Styde\Html\FormModel\FieldCollection;
use Illuminate\Contracts\Support\Htmlable;
use Styde\Html\FormModel\ButtonCollection;
use Styde\Html\FormModel\Concerns\HasFields;
use Styde\Html\FormModel\Concerns\HasButtons;

class FormModel implements Htmlable
{
    use HasFields, HasButtons, Macroable;

    /**
     * @var \Styde\Html\FormBuilder
     */
    protected $formBuilder;

    /**
     * @var \Styde\Html\Theme
     */
    protected $theme;

    /**
     * @var \Styde\Html\Form
     */
    public $form;

    /**
     * @var \Styde\Html\FormModel\FieldCollection
     */
    public $fields;

    /**
     * @var \Styde\Html\FormModel\ButtonCollection
     */
    public $buttons;

    public $method = 'post';

    public $customTemplate;
    /**
     * @var HtmlBuilder
     */
    private $htmlBuilder;
    /**
     * @var FieldBuilder
     */
    private $fieldBuilder;

    /**
     * Form Model constructor.
     *
     * @param HtmlBuilder $htmlBuilder
     * @param FormBuilder $formBuilder
     * @param FieldBuilder $fieldBuilder
     * @param Theme $theme
     * @internal param FieldCollection $fields
     * @internal param ButtonCollection $buttons
     */
    public function __construct(HtmlBuilder $htmlBuilder, FormBuilder $formBuilder, FieldBuilder $fieldBuilder, Theme $theme)
    {
        $this->formBuilder = $formBuilder;
        $this->htmlBuilder = $htmlBuilder;
        $this->fieldBuilder = $fieldBuilder;
        $this->theme = $theme;

        $this->fields = $this->newFieldCollection($this->fieldBuilder);
        $this->buttons = $this->newButtonCollection();
    }

    /**
     * Link the form to an specific route.
     *
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     * @return Form
     */
    public function route($name, $parameters = [], $absolute = true)
    {
        return $this->form->route($name, $parameters, $absolute);
    }

    public function newFieldCollection(FieldBuilder $fieldBuilder)
    {
        return new FieldCollection($fieldBuilder);
    }

    public function newButtonCollection()
    {
        return new ButtonCollection;
    }

    /**
     * Set the form method as post.
     *
     * @return $this
     */
    public function forCreation()
    {
        $this->method = 'post';
        return $this;
    }

    /**
     * Set the form method as put.
     *
     * @return $this
     */
    public function forUpdate()
    {
        $this->method = 'put';
        return $this;
    }

    /**
     * Run the setup.
     *
     * @return void
     */
    protected function runSetup()
    {
        if ($this->form) {
            return;
        }

        $this->form = $this->formBuilder->make($this->method());

        if ($this->method() == 'post') {
            $this->creationSetup();
        } elseif ($this->method() == 'put') {
            $this->updateSetup();
        } else {
            $this->setup();
        }
    }

    /**
     * Get Method
     *
     * @return string
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * Setup common form attributes, fields and buttons.
     *
     * @return void
     */
    public function setup()
    {
        //...
    }

    /**
     * Setup form attributes, fields and buttons for creation.
     *
     * @return void
     */
    public function creationSetup()
    {
        $this->setup();
    }

    /**
     * Setup form attributes, form fields and buttons for update.
     *
     * @return void
     */
    public function updateSetup()
    {
        $this->setup();
    }

    /**
     * Set a new custom template
     *
     * @param string $template
     * @return $this
     */
    public function template($template)
    {
        $this->customTemplate = $template;

        return $this;
    }

    /**
     * Set a new Model
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return $this
     */
    public function model($model)
    {
        $this->formBuilder->setCurrentModel($model);

        return $this;
    }

    /**
     * Set the novalidate attribute for a form, so developers can
     * skip HTML5 validation, in order to test backend validation
     * in a local or development environment.
     *
     * @param boolean $value
     * @return $this
     */
    public function novalidate($value = true)
    {
        $this->runSetup();

        $this->form->novalidate($value);

        return $this;
    }

    /**
     * Render all form to Html
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->render();
    }

    /**
     * @param string|null $customTemplate
     * @return string
     */
    public function render($customTemplate = null)
    {
        $this->runSetup();

        return $this->theme->render($customTemplate ?: $this->customTemplate ?: '@form', [
            'form' => $this->form,
            'fields' => $this->fields,
            'buttons' => $this->buttons,
        ]);
    }
    
    public function scripts()
    {
        $this->runSetup();

        $scripts = [];

        foreach ($this->fields->onlyFields() as $name => $field) {
            $scripts = array_merge($scripts, $field->scripts);
        }

        return array_values(array_unique($scripts));
    }

    public function renderScripts()
    {
        return new HtmlString(array_reduce($this->scripts(), function ($result, $script) {
            return $result.Html::script($script);
        }, ''));
    }

    public function styles()
    {
        $this->runSetup();

        $styles = [];

        foreach ($this->fields->onlyFields() as $name => $field) {
            $styles = array_merge($styles, $field->styles);
        }

        return array_values(array_unique($styles));
    }

    public function renderStyles()
    {
        return new HtmlString(array_reduce($this->styles(), function ($result, $style) {
            return $result.Html::style($style);
        }, ''));
    }
    
    /**
     * Validate the request with the validation rules specified
     *
     * @param Request|null $request
     * @return mixed
     */
    public function validate(Request $request = null)
    {
        return ($request ?: request())->validate($this->getValidationRules());
    }

    /**
     * Get all rules of validation
     *
     * @return array
     */
    public function getValidationRules()
    {
        $this->runSetup();

        $rules = [];

        foreach ($this->fields->all() as $name => $field) {
            if ($field instanceof Field && $field->included) {
                $rules[$name] = $field->getValidationRules();
            }
        }

        return $rules;
    }

    /**
     * Dynamically handle calls to the form model.
     *
     * @param  string $method
     * @param  array $parameters
     *
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters = [])
    {
        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }

    /**
     * Get a field by name.
     *
     * @param  string $name
     *
     * @return Field
     */
    public function __get($name)
    {
        return $this->fields->$name;
    }
}
