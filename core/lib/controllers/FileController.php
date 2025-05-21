<?php

namespace Simp\Core\lib\controllers;

use Simp\Core\lib\forms\FileAddForm;
use Simp\Core\lib\themes\View;
use Simp\FormBuilder\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class FileController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function add_file(...$args):Response|RedirectResponse
    {
        extract($args);

        $formBase = new FormBuilder(new FileAddForm);
        $formBase->getFormBase()->setFormMethod('POST');
        $formBase->getFormBase()->setFormEnctype('multipart/form-data');
        $formBase->getFormBase()->setFormAction('/file/add');
        return new Response(View::view('default.view.file_form',['_form'=>$formBase]), Response::HTTP_OK);
    }
}