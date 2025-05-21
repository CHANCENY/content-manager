<?php

namespace Simp\Core\lib\forms;

use Simp\Default\FileField;
use Simp\Default\TextAreaField;
use Simp\FormBuilder\FormBase;

class FileAddForm extends FormBase
{

    protected bool $validated = true;
    public function getFormId(): string
    {
       return "add_file_form";
    }

    public function buildForm(array &$form): array
    {
        $form['files'] = [
            'type' => 'file',
            'label' => 'Upload Files',
            'name' => 'files',
            'id' => 'files',
            'class' => ['form-control'],
            'options' => [
                'multiple' => 'multiple',
            ],
            'limit' => 10,
            'description' => 'Upload multiple files at once max 10 files.',
            'handler'=> FileField::class,
        ];
        $form['files_urls'] = [
            'type' => 'textarea',
            'label' => 'File URLs',
            'name' => 'files_urls',
            'id' => 'files_urls',
            'class' => ['form-control'],
            'options' => [
                'rows' => 5,
                'cols' => 5,
            ],
            'limit' => 1,
            'description' => 'Enter the URLs of the files you want to upload. One URL per line.',
            'handler' => TextAreaField::class,
        ];
        $form['submit'] = [
            'type' => 'submit',
            'default_value' => 'Submit',
            'name' => 'submit',
            'id' => 'submit',
            'class' => ['btn', 'btn-primary'],
        ];
        return $form;
    }

    public function validateForm(array $form): void
    {
       $files = $form['files']->getValue();
       $urls = $form['files_urls']->getValue();
       if (empty($files['name'][0]) && empty($urls)) {
           $form['files']->setError('Please select a file or enter the URLs of the files you want to upload');
           $this->validated = false;
       }
    }

    public function submitForm(array &$form): void
    {
       if ($this->validated) {
           $files = $form['files']->getValue();
           $urls = $form['files_urls']->getValue();
           dump($files);
       }
    }
}