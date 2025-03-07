<?php

namespace Simp\Core\lib\forms;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\modules\messager\Messager;
use Simp\Core\modules\user\entity\User;
use Simp\Default\FileField;
use Simp\Default\TextAreaField;
use Simp\FormBuilder\FormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ProfileEditForm extends FormBase
{
    protected bool $validated = false;
    public function getFormId(): string
    {
        return 'profile_edit';
    }

    public function buildForm(array &$form): array
    {
        $request = Request::createFromGlobals();
        $user = User::load($request->get('uid'));
        if ($profile = $user->getProfile()) {
            $form['first_name'] = [
                'label' => 'First Name',
                'name' => 'first_name',
                'type' => 'text',
                'id' => 'first_name',
                'class' => ['form-control'],
                'default_value' => $profile->getFirstName(),
            ];
            $form['last_name'] = [
                'label' => 'Last Name',
                'name' => 'last_name',
                'type' => 'text',
                'id' => 'last_name',
                'class' => ['form-control'],
                'default_value' => $profile->getLastName(),
            ];
            $form['profile_image'] = [
                'label' => 'Profile Image',
                'name' => 'profile_image',
                'type' => 'file',
                'id' => 'profile_image',
                'class' => ['form-control'],
                'handler' => FileField::class,
            ];
            $form['description'] = [
                'label' => 'Description',
                'name' => 'description',
                'type' => 'textarea',
                'id' => 'description',
                'class' => ['form-control'],
                'handler' => TextareaField::class,
                'default_value' => $profile->getDescription(),
            ];
            $form['submit'] = [
                'type' => 'submit',
                'name' => 'submit',
                'id' => 'submit',
                'class' => ['btn btn-primary'],
                'default_value' => 'Submit',
            ];
            return $form;
        }
        return $form;
    }

    public function validateForm(array $form): void
    {

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
        $user = User::load($request->get('uid'));
        $profile = $user->getProfile();
        //TODO: upload image here if exist.
        if (!empty($form['first_name']->getValue())) {
            $profile->setFirstName( $form['first_name']->getValue() );
        }
        if (!empty($form['last_name']->getValue())) {
           $profile->setLastName( $data['last_name'] = $form['last_name']->getValue() );
        }
        if (!empty($form['description']->getValue())) {
            $profile->setDescription( $form['description']->getValue() );
        }

        if ($profile->update()) {
            $redirect = new RedirectResponse($request->server->get('REDIRECT_URL'));
            Messager::toast()->addMessage("{$user->getName()} profile has been updated.");
            $redirect->send();
        }
        else {
            Messager::toast()->addError("{$user->getName()} profile could not be updated.");
        }
    }
}