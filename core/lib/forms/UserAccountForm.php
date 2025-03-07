<?php

namespace Simp\Core\lib\forms;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\modules\messager\Messager;
use Simp\Core\modules\timezone\TimeZone;
use Simp\Core\modules\user\entity\User;
use Simp\Fields\FieldBase;
use Simp\FormBuilder\FormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Yaml\Yaml;

class UserAccountForm extends FormBase
{

    protected array $entity_form = [];
    private bool $validated = true;

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function __construct()
    {
        $user_form = Caching::init()->get('default.admin.user.entity.form');
        if (file_exists($user_form)) {
            $this->entity_form = Yaml::parseFile($user_form);
        }

        $timezone = new  TimeZone();
        $list = $timezone->getSimplifiedTimezone();
        sort($list);
        $this->entity_form['fields']['user_prefer_timezone']['option_values'] = $list;
    }

    public function getFormId(): string
    {
       return 'user_account_form';
    }

    public function buildForm(array &$form): array
    {
        return [...$form, ...$this->entity_form['fields']];
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

    /**
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function submitForm(array &$form): void
    {
       if ($this->validated) {
          $user_data['name'] = $form['name']?->getValue();
          $user_data['mail'] = $form['mail']->getValue();
          $user_data['time_zone'] = $form['user_prefer_timezone']->getValue();
          $user_data['password'] = $form['cond_password']->get('password');
          $user_data['roles'][] = $form['roles']->getValue();

          $timezone = new TimeZone();
          $list = array_keys($timezone->getSimplifiedTimezone());
          sort($list);
          $user_data['time_zone'] = $list[$user_data['time_zone']] ?? "Africa/Blantyre";

          $user = User::create($user_data);
          if ($user === false) {
              Messager::toast()->addError("Unable to create account due to already existing name or email");
          }
          elseif ($user === null) {
              Messager::toast()->addError("Unable to create account due to incomplete data");
          }
          else {
              Messager::toast()->addMessage("Account created successfully");
              $redirect = new RedirectResponse('/');
             // $redirect->send();
          }
       }
    }
}