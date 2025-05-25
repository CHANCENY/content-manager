<?php

namespace Simp\Core\lib\forms;

use Simp\Core\modules\cron\Cron;
use Simp\Default\FieldSetField;
use Simp\Default\SelectField;
use Simp\Default\TextAreaField;
use Simp\FormBuilder\FormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AddCronForm extends FormBase {

    public function getFormId(): string
    {
        return "cron_form";
    }

    public function buildForm(array &$form): array
    {
        $form['title'] = [
            'type'=> 'text',
            'name' => 'title',
            'id' => 'title',
            'class'=> [],
            'label' => 'Cron title'
        ];

        $options = [
    'every|minute' => 'Every minute',
    'every|hour'   => 'Every hour',
    'every|day'    => 'Every day',
    'every|week'   => 'Every week',
    'every|month'  => 'Every month',
    'every|year'   => 'Every year',
    ];

       $form['timing_wrapper'] = [
        'type' => 'fieldset',
        'name' => 'timing_wrapper',
        'label' => 'Timing Settings',
        'id'=> 'timing_wrapper',
        'class' => [],
        'inner_field' => [
            'every_timing' => [
                  'type' => 'select',
                  'name' => 'every_timing',
                  'id' => 'timing',
                  'class' => [],
                  'label' => 'Every Timing',
                  'option_values' => $options,
                  'handler'=> SelectField::class,
                  'description'=> 'select option here means the cron will run of every bases'
            ],
            'once_timing' => [
                'type' => 'date',
                'name' => 'once_timing',
                'label' => 'Once Date',
                'id' => 'once_timing',
                'class' => [],
                'description' => 'give value to this field if this cron is to run once on specific date'
            ],
            'ontime_timing_wrapper' => [
                'type' => 'fieldset',
                'name' => 'ontime_timing_wrapper',
                'label' => 'Ontime Settings',
                'id' => 'ontime_timing_wrapper',
                'class' => [],
                'handler' => FieldSetField::class,
                'inner_field' => [
                    'ontime_every_timing' => [
                        'type' => 'select',
                        'name' => 'timing',
                        'id' => 'timing',
                        'class' => [],
                        'label' => 'Ontime Timing',
                        'option_values' => $options,
                        'handler'=> SelectField::class
                    ],
                    'ontime_timing' => [
                        'type' => 'datetime-local',
                        'name' => 'ontime_timing',
                        'label' => 'On Time',
                        'id'=> 'ontime_timing',
                        'class' => [],
                        'description'=> 'give value to this field if this cron is to run every day on this time'
                    ]
                ],
            ],
        ],
        'handler' => FieldSetField::class
       ];

        $form['description'] = [
            'type' => 'textarea',
            'name' => 'description',
            'label' => 'Description',
            'id' => 'description',
            'class' => [],
            'handler' => TextAreaField::class
        ];

        $form['submit'] = [
            'type' => 'submit',
            'name' => 'submit',
            'label' => '',
            'default_value' => 'Submit',
            'id'=> 'submit'
        ];
        return $form;
    }

    public function validateForm(array $form): void
    {

    }

    public function submitForm(array &$form): void
    {
        $data = \array_map(function($item){ return $item->getValue(); },$form);
        $name = \str_replace(' ', '_', $data['title']);
        $name = \strtolower($name);
        $cron[$name]['title'] = $data['title'];
        $cron[$name]['description'] = $data['description'];

        if (!empty($data['timing_wrapper']['every_timing']) && \strlen($data['timing_wrapper']['every_timing']) > 5) {
            $cron[$name]['timing'] = $data['timing_wrapper']['every_timing'];
        }
        elseif (!empty($data['timing_wrapper']['once_timing']) && \strlen($data['timing_wrapper']['once_timing']) > 5) {
            $cron[$name]['timing'] = 'once|'.$data['timing_wrapper']['once_timing'];
        }
        elseif(!empty($data['timing_wrapper']['ontime_timing_wrapper']['ontime_every_timing']) && \strlen($data['timing_wrapper']['ontime_timing_wrapper']['ontime_every_timing']) > 5) {
            $cron[$name]['timing'] = 'ontime|'.$data['timing_wrapper']['ontime_timing_wrapper']['ontime_timing']
            . "@". $data['timing_wrapper']['ontime_timing_wrapper']['ontime_every_timing'];
        }
        Cron::factory()->add($name, reset( \array_values($cron)));
        $redirect = new RedirectResponse('/cron/manage');
        $redirect->send();
    }



}
