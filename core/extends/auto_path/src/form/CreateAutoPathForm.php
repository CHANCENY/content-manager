<?php

namespace Simp\Core\extends\auto_path\src\form;

use Simp\Default\SelectField;
use Simp\FormBuilder\FormBase;

class CreateAutoPathForm extends FormBase
{

    public function getFormId(): string
    {
       return 'create_auto_path';
    }

    public function buildForm(array &$form): array
    {
        $form['type'] = [
            'type' => 'select',
            'label' => 'Type',
            'name' => 'type',
            'id' => 'type',
            'class' => ['form-control'],
            'option_values' => [],
            'handler' => SelectField::class
        ];

        return $form;
    }

    public function validateForm(array $form): void
    {
        // TODO: Implement validateForm() method.
    }

    public function submitForm(array &$form): void
    {
        // TODO: Implement submitForm() method.
    }
}