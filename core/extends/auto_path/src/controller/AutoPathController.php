<?php

namespace Simp\Core\extends\auto_path\src\controller;

use Simp\Core\extends\auto_path\src\form\CreateAutoPathForm;
use Simp\Core\lib\themes\View;
use Simp\FormBuilder\FormBuilder;
use Symfony\Component\HttpFoundation\Response;

class AutoPathController
{
    public function auto_path_create(...$args): Response {
        $form_base = new FormBuilder(new CreateAutoPathForm());
        $form_base->getFormBase()->setFormMethod('POST');
        $form_base->getFormBase()->setFormEnctype('multipart/form-data');
        return new Response(View::view('default.view.auto_path_create',['_form'=>$form_base]), Response::HTTP_OK);
    }
}