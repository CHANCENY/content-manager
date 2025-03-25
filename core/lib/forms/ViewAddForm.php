<?php

namespace Simp\Core\lib\forms;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\modules\messager\Messager;
use Simp\Core\modules\structures\content_types\ContentDefinitionManager;
use Simp\Core\modules\structures\views\ViewsManager;
use Simp\Default\FieldSetField;
use Simp\Default\SelectField;
use Simp\Fields\FieldBase;
use Simp\FormBuilder\FormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ViewAddForm extends FormBase
{

    protected bool $validated = true;

    public function getFormId(): string
    {
       return 'view_add';
    }

    public function buildForm(array &$form): array
    {
        $routes = Caching::init()->get('system.routes.keys');
        $routes = array_values($routes);
        $routes = array_combine($routes, $routes);

        $content_list = ContentDefinitionManager::contentDefinitionManager()->getContentTypes();
        $content_list = array_keys($content_list);
        $content_list = array_combine($content_list, $content_list);

        $form['wrapper'] = [
            'type' => 'fieldset',
            'handler' => FieldSetField::class,
            'name' => 'wrapper',
            'class' => ['form-group'],
            'id' => 'wrapper',
            'label' =>'View basic information',
            'inner_field' => [
                'name' => [
                    'type' => 'text',
                    'label' => 'View Name',
                    'required' => true,
                    'id' => 'name',
                    'class' => ['form-control'],
                    'name' => 'name',
                ],
                'description' => [
                    'type' => 'text',
                    'label' => 'Description',
                    'required' => true,
                    'id' => 'description',
                    'class' => ['form-control'],
                    'name' => 'description',
                ],
            ]
        ];

        $form['page_settings'] = [
            'type' => 'fieldset',
            'handler' => FieldSetField::class,
            'name' => 'view_settings',
            'class' => ['form-group'],
            'id' => 'view_settings',
            'label' =>'Page settings',
            'inner_field' => [
                'content_type' => [
                    'type' => 'select',
                    'label' => 'Content Type',
                    'required' => true,
                    'id' => 'content_type',
                    'class' => ['form-control'],
                    'name' => 'content_type',
                    'option_values' => [
                        'all' => 'All',
                        ...$content_list,
                    ],
                    'handler' => SelectField::class,
                    'default_value' => 'administrator',
                ],
                'permission' => [
                    'type' => 'select',
                    'label' => 'Permission',
                    'required' => true,
                    'id' => 'permission',
                    'class' => ['form-control'],
                    'name' => 'permission',
                    'option_values' => [
                        'administrator' => 'Administrator',
                        'content_creator' => 'Content Creator',
                        'manager' => 'Manager',
                        'authenticated' => 'Authenticated',
                        'anonymous' => 'Anonymous',
                    ],
                    'handler' => SelectField::class,
                    'default_value' => 'administrator',
                    'options' => [
                        'multiple' => 'multiple',
                    ]
                ]
            ]
        ];

        $form['submit'] = [
            'type' => 'submit',
            'name' => 'submit',
            'default_value' => 'Save View',
            'id' => 'submit',
            'class' => ['btn btn-primary'],
        ];

        return $form;
    }

    public function validateForm(array $form): void
    {
        foreach ($form as $field) {
            if ($field instanceof FieldBase && isset($field->getField()['inner_field'])) {
                $this->validate_recursive($field->getField()['inner_field']);
            }
            elseif ($field instanceof FieldBase && $field->getRequired() === 'required' && empty($field->getValue())) {
                $field->setError($field->getLabel().' is required');
                $this->validated = false;
            }
        }
    }

    private function validate_recursive(array &$fields): void
    {
        foreach ($fields as &$field) {
            if ($field instanceof FieldBase && isset($field->getField()['inner_field'])) {
                $this->validate_recursive($field->getField()['inner_field']);
            }
            elseif ($field instanceof FieldBase && $field->getRequired() === 'required' && empty($field->getValue())) {
                $field->setError($field->getLabel().' is required');
                $this->validated = false;
            }
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
        if ($this->validated) {

            $data = array_map(function ($field) {
                return $field->getValue();
            },$form);
            $view_data = [
                ...$data['wrapper'],
                ...$data['page_settings'],
                'displays' => []
            ];

            $redirect = new RedirectResponse('/admin/structure/views');
            $redirect->setStatusCode(302);

            unset($data['submit']);
            $views = ViewsManager::viewsManager();
            $name = $view_data['name'] ?? '';

            if (!empty($name)) {
                $name = str_replace(' ', '.', $name);
                $name = 'view.'.strtolower($name);
            }

            if ($views->addView($name, $view_data)) {
                Messager::toast()->addMessage("Views successfully added");
                $redirect->send();
            }
        }
    }
}