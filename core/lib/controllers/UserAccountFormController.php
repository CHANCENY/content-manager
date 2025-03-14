<?php

namespace Simp\Core\lib\controllers;



use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\forms\UserAccountForm;
use Simp\Core\lib\themes\View;
use Simp\Core\modules\config\ConfigManager;
use Simp\Core\modules\messager\Messager;
use Simp\Core\modules\user\current_user\CurrentUser;
use Simp\FormBuilder\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserAccountFormController
{
    /**
     * @param ...$args
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function user_account_form_controller(...$args): Response
    {
        $config = ConfigManager::config()->getConfigFile('account.setting');
        $current = CurrentUser::currentUser();
        $redirect = new RedirectResponse('/');
        if ($config?->get('allow_account_creation') === 'administrator' && empty($current?->isIsAdmin())) {
            $redirect->send();
        }
        elseif ($config?->get('allow_account_creation') === 'visitor' && !empty($current?->isIsAdmin())) {
            Messager::toast()->addWarning("Access denied for this user");
            $redirect->send();
        }

        $form_base = new FormBuilder(new UserAccountForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.user_account_form',['_form'=>$form_base]),200);
    }
}