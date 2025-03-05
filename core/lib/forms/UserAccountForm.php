<?php

namespace Simp\Core\lib\forms;

use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\modules\user\entity\UserEntity;
use Simp\Fields\FieldBase;
use Simp\FormBuilder\FormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAccountForm extends FormBase
{

    protected UserEntity $entity;

    private bool $validated = true;

    public function __construct()
    {
        $this->entity = new UserEntity(Request::createFromGlobals()->get('uid',1));
    }

    public function getFormId(): string
    {
       return 'user_account_form';
    }

    public function buildForm(array &$form): array
    {
        $this_fields = $this->entity->getEntityFormFields();
        $this_fields['user_prefer_timezone']['default_value'] = [
            '' => 'Select Timezone',
        ];

        return [
            ...$form,
            ...$this_fields,
        ];
    }

    public function validateForm(array $form): void
    {
        foreach ($form as $key=>$field) {
            if ($field instanceof FieldBase) {
                if ($field->getRequired() === 'required' && empty($field->getValue())) {
                    $this->validated = false;
                }
            }
        }

        if ($form['cond_password']->get('password') !== $form['cond_password']->get('password_confirm')) {
            $this->validated = false;
            $form['cond_password']->setError("Passwords do not match");
        }
    }

    public function submitForm(array &$form): void
    {
       if ($this->validated) {
          $user_data['name'] = $form['name']?->getValue();
          $user_data['mail'] = $form['mail']->getValue();
          $user_data['prefer_timezone'] = $form['user_prefer_timezone']->getValue();
          $user_data['password'] = $form['cond_password']->get('password');
          $user_data['confirm_password'] = $form['cond_password']->get('password_confirm');
          $user_data['roles'][] = $form['roles']->getValue();

          Caching::init()->rebuild();
          if($this->entity->addAccount($user_data)) {
              $redirect = new RedirectResponse('/');
              $redirect->send();
           }
       }
    }
}