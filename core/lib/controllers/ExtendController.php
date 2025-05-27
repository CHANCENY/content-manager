<?php

namespace Simp\Core\lib\controllers;


use Simp\Core\lib\forms\ExtendAddFrom;
use Simp\Core\lib\themes\View;
use Simp\FormBuilder\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ExtendController
{

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function extend_manage(...$args): Response
    {
        return new Response(View::view('default.view.extend_manage'), Response::HTTP_OK);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function extend_manage_add(...$args): Response|RedirectResponse
    {
        $form_base = new FormBuilder(new ExtendAddFrom());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.extend_manage_add',['_form'=>$form_base]), Response::HTTP_OK);
    }
}