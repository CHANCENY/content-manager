<?php

namespace Simp\Core\lib\forms;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\modules\files\entity\File;
use Simp\Core\modules\files\uploads\FormUpload;
use Simp\Core\modules\files\uploads\UrlUpload;
use Simp\Core\modules\user\current_user\CurrentUser;
use Simp\Default\FileField;
use Simp\Default\TextAreaField;
use Simp\FormBuilder\FormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function submitForm(array &$form): void
    {
       if ($this->validated) {
           $files = $form['files']->getValue();
           $urls = $form['files_urls']->getValue();

           $uploaded_files = [];

           if (!empty($files['name'][0])) {
               foreach ($files['name'] as $key => $file) {

                   // file upload object.
                   $file_object = [
                       'name' => $file,
                       'type' => $files['type'][$key],
                       'tmp_name' => $files['tmp_name'][$key],
                       'error' => $files['error'][$key],
                       'size' => $files['size'][$key],
                       'full_path' => $files['full_path'][$key],
                   ];

                   $form_uploader = new FormUpload();
                   $form_uploader->addAllowedExtension("image/*");
                   $form_uploader->addAllowedExtension("application/*");
                   $form_uploader->addAllowedExtension("video/*");
                   $form_uploader->addAllowedExtension("audio/*");
                   $form_uploader->addAllowedExtension("text/*");

                   // max allowed is 10 mb
                   $form_uploader->addAllowedMaxSize(1024 * 1024 * 10);
                   $form_uploader->addFileObject($file_object);
                   $form_uploader->validate();

                   // make sure the directory exists
                   if (!is_dir('public://files')) {
                       @mkdir('public://files', 0777, true);
                   }

                   if ($form_uploader->isValidated()) {
                       $form_uploader->moveFileUpload('public://files/'.$form_uploader->getParseFilename());
                       $uploaded_files[] = $form_uploader->getFileObject();
                   }
               }

           }

           if (!empty($urls)) {
               $urls = explode("\n", $urls);
               $urls = array_filter($urls);
               $urls = array_map('trim', $urls);

               foreach ($urls as $url) {

                   $url_uploader = new UrlUpload();
                   $url_uploader->addAllowedExtension("image/*");
                   $url_uploader->addAllowedExtension("application/*");
                   $url_uploader->addAllowedExtension("video/*");
                   $url_uploader->addAllowedExtension("audio/*");
                   $url_uploader->addAllowedExtension("text/*");
                   $url_uploader->addAllowedMaxSize(1024 * 1024 * 10);
                   $url_uploader->addUrl($url);
                   $url_uploader->validate();

                   // make sure the directory exists
                   if (!is_dir('public://files')) {
                       @mkdir('public://files', 0777, true);
                   }

                   if ($url_uploader->isValidated()) {
                       $file_name = $url_uploader->getParseFilename();
                       $url_uploader->moveFileUpload('public://files/'.$file_name);
                       $uploaded_files[] = $url_uploader->getFileObject();
                   }

               }

           }

           foreach ($uploaded_files as $file) {

               $file['uid'] = CurrentUser::currentUser()?->getUser()->getUid();
               $file['uri'] = $file['file_path'];
               $file_o = File::create($file);


           }

           $redirect = new RedirectResponse('/admin/content');
           $redirect->send();
       }
    }
}