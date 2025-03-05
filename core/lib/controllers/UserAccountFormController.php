<?php

namespace Simp\Core\lib\controllers;



use Simp\Core\lib\forms\UserAccountForm;
use Simp\Core\lib\themes\View;
use Simp\FormBuilder\FormBuilder;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserAccountFormController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function user_account_form_controller(...$args): Response
    {
        $form_base = new FormBuilder(new UserAccountForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.user_account_form',['_form'=>$form_base]),200);
    }
}