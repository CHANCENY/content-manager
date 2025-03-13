<?php

namespace Simp\Core\lib\forms;

use Simp\Core\modules\structures\content_types\ContentDefinitionManager;
use Simp\Default\FieldSetField;
use Simp\Default\SelectField;
use Simp\Default\TextAreaField;
use Symfony\Component\HttpFoundation\Request;

class ContentTypeFieldEditForm extends ContentTypeFieldForm
{

    public function getFormId(): string
    {
        return 'ContentTypeFieldEditForm';
    }

    public function buildForm(array &$form): array
    {
        $request = Request::createFromGlobals();
        $name = $request->get('machine_name');
        $field_name = $request->get('field_name');

        $field = ContentDefinitionManager::contentDefinitionManager()->getField($name,$field_name);
        if (!empty($field)) {

            $list = [];
            foreach ($field['options'] as $key=>$other) {
             $list[$key] = $other;
            }
            $form['title'] = [
                'type' => 'text',
                'label' => 'Field Name',
                'id' => 'title',
                'required' => true,
                'class' => ['form-control'],
                'name' => 'title',
                'default_value' => $field['label'] ?? null,
            ];
            $form['type'] = [
                'type' => 'select',
                'label' => 'Field Type',
                'id' => 'name',
                'required' => true,
                'class' => ['form-control'],
                'handler' => SelectField::class,
                'option_values' => [
                    'text' => 'Text',
                    'number' => 'Number',
                    'date' => 'Date',
                    'datetime' => 'DateTime',
                    'datetime-local' => 'DateTime-Local',
                    'month' => 'Month',
                    'week' => 'Week',
                    'time' => 'Time',
                    'email' => 'Email',
                    'hidden' => 'Hidden',
                    'password' => 'Password',
                    'reset' => 'Reset',
                    'search' => 'Search',
                    'url' => 'URL',
                    'tel' => 'Tel',
                    'color' => 'Color',
                    'range' => 'Range',
                    'select' => 'Select',
                    'file' => 'File',
                ],
                'name' => 'name',
                'default_value' => $field['type'] ?? null,
            ];
            $form['description'] = [
                'type' => 'textarea',
                'label' => 'Description',
                'id' => 'description',
                'required' => true,
                'class' => ['form-control'],
                'name' => 'description',
                'handler' => TextareaField::class,
                'default_value' => $field['description'] ?? null,
            ];
            $form['option'] = [
                'type' => 'fieldset',
                'label' => 'Field options',
                'id' => 'option',
                'required' => true,
                'class' => ['form-control'],
                'handler' => FieldSetField::class,
                'inner_field' => [
                    'field_id' => [
                        'type' => 'text',
                        'label' => 'Field ID',
                        'id' => 'field_id',
                        'required' => true,
                        'class' => ['form-control'],
                        'name' => 'field_id',
                        'default_value' => $field['id'],
                    ],
                    'field_classes' => [
                        'type' => 'text',
                        'label' => 'Field Classes',
                        'id' => 'field_classes',
                        'class' => ['form-control'],
                        'name' => 'field_classes',
                        'description' => 'give css class in space separated',
                        'default_value' => implode(' ', $field['class'] ?? []),
                    ],
                    'field_required' => [
                        'type' => 'select',
                        'label' => 'Field Required',
                        'id' => 'field_required',
                        'required' => true,
                        'class' => ['form-control'],
                        'name' => 'field_required',
                        'option_values' => [
                            'yes' => 'Yes',
                            'no' => 'No',
                        ],
                        'handler' => SelectField::class,
                        'default_value' => $field['required'] === true ? 'yes' : 'no',
                    ],
                    'field_default' => [
                        'type' => 'text',
                        'label' => 'Field Default Value',
                        'id' => 'field_default',
                        'class' => ['form-control'],
                        'name' => 'field_default',
                        'default_value' => $field['default_value'] ?? null,
                    ],
                    'others' => [
                        'type' => 'textarea',
                        'label' => 'Others',
                        'id' => 'others',
                        'class' => ['form-control'],
                        'name' => 'others',
                        'description' => 'give other field options',
                        'handler' => TextareaField::class,
                        'default_value' => implode('\n',$list) ?? null,
                    ]
                ],
                'name' => 'option'
            ];
            $form['submit'] = [
                'type' => 'submit',
                'default_value' => 'Save field',
                'id' => 'submit',
                'name' => 'submit',
                'class' => ['btn', 'btn-primary'],
            ];
        }
        return $form;
    }

}