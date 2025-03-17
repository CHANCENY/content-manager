<?php

namespace Simp\Core\lib\forms;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\modules\messager\Messager;
use Simp\Core\modules\structures\content_types\ContentDefinitionManager;
use Simp\Default\BasicField;
use Simp\Default\ConditionalField;
use Simp\Default\DetailWrapperField;
use Simp\Default\FieldSetField;
use Simp\Default\FileField;
use Simp\Default\SelectField;
use Simp\Default\TextAreaField;
use Simp\FormBuilder\FormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ContentTypeInnerFieldForm extends FormBase
{

    protected bool $validated = true;
    public function getFormId(): string
    {
        return 'content_type_manage_add_field';
    }

    public function buildForm(array &$form): array
    {
        $form['title'] = [
            'type' => 'text',
            'label' => 'Field Name',
            'id' => 'title',
            'required' => true,
            'class' => ['form-control'],
            'name' => 'title',
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
                'textarea' => 'TextArea',
                'fieldset' => 'FieldSet',
                'details' => 'Details',
                'conditional' => 'Conditional fieldset'
            ],
            'name' => 'name'
        ];
        $form['description'] = [
            'type' => 'textarea',
            'label' => 'Description',
            'id' => 'description',
            'required' => true,
            'class' => ['form-control'],
            'name' => 'description',
            'handler' => TextareaField::class,
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
                ],
                'field_classes' => [
                    'type' => 'text',
                    'label' => 'Field Classes',
                    'id' => 'field_classes',
                    'class' => ['form-control'],
                    'name' => 'field_classes',
                    'description' => 'give css class in space separated'
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
                ],
                'field_default' => [
                    'type' => 'text',
                    'label' => 'Field Default Value',
                    'id' => 'field_default',
                    'class' => ['form-control'],
                    'name' => 'field_default',
                ],
                'others' => [
                    'type' => 'textarea',
                    'label' => 'Others',
                    'id' => 'others',
                    'class' => ['form-control'],
                    'name' => 'others',
                    'description' => 'give other field options',
                    'handler' => TextareaField::class,
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
        return $form;
    }

    public function validateForm(array $form): void
    {
        if (empty($form['title']?->getValue())) {
            $this->validated = false;
        }

        if (empty($form['type']?->getValue())) {
            $this->validated = false;
        }

        if (empty($form['option']?->getValue()['field_id'])) {
            $this->validated = false;
        }

        if (empty($form['option']?->getValue()['field_required'])) {
            $this->validated = false;
        }
    }

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function submitForm(array &$form): void
    {
        $request = Request::createFromGlobals();
       if ($this->validated) {
           $data = array_map(function($item) {
               return $item->getValue();
           },$form);

           $field = [
               'type' => $data['type'],
               'label' => $data['title'],
               'required' => $data['option']['field_required'] === 'yes',
               'class' => explode(' ',$data['option']['field_classes']),
               'default_value' => $data['option']['field_default'],
               'id' => $data['option']['field_id'],
           ];
           $options = $data['option']['others'];
           if (!empty($options)) {
               $list = explode('\n',$options);
               $options = [];
               foreach ($list as $item) {
                   $lis = explode('=',$item);
                   if (count($lis) == 2) {
                       $options[$lis[0]] = $lis[1];
                   }
                   else {
                       $options[end($lis)] = end($lis);
                   }
               }
               $field['options'] = $options;
           }
           $field['handler'] = match ($data['type']) {
               'file' => FileField::class,
               'textarea' => TextareaField::class,
               'select' => SelectField::class,
               'fieldset' => FieldSetField::class,
               'details' => DetailWrapperField::class,
               'conditional' => ConditionalField::class,
               default => BasicField::class,
           };

           $persist = true;
          
           if (in_array($data['type'], ['details', 'fieldset', 'conditional'])) {
              $field['inner_field'] = [];
              if ($data['type'] === 'conditional') {
                $field['conditions'] = [];
              }
              $persist = false;
           }
           $name_content = $request->get('machine_name');
           $name = str_replace(' ','_',$field['label']);
           $name = strtolower($name);
           $field['name'] = $name_content.'_'.$name;

           $original_field = ContentDefinitionManager::contentDefinitionManager()->getContentType($name_content);
           $original_field_data = $original_field['fields'][$request->get('field_name')] ?? [];
           $original_field_data['inner_field'][$field['name']] = $field;

           $parent_field = $request->get('field_name');
           
           $persist_override = $field;
            if (in_array($data['type'], ['details', 'fieldset', 'conditional'])) {
               $persist_override = [];
            }

           if (ContentDefinitionManager::contentDefinitionManager()->addField($name_content, $parent_field, $original_field_data, $persist, $persist_override)) {
               Messager::toast()->addMessage("Field '$name' has been created");
           }
           $redirect = new RedirectResponse('/admin/structure/content-type/'.$name_content. '/manage');
           $redirect->send();
       }
    }
}