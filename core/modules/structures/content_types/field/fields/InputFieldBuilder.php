<?php

namespace Simp\Core\modules\structures\content_types\field\fields;

use Simp\Core\lib\themes\View;
use Simp\Core\modules\structures\content_types\field\FieldBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class InputFieldBuilder implements FieldBuilderInterface
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function build(Request $request): string
    {
        return View::view('default.view.basic.simple');
    }

    public function fieldArray(Request $request): array
    {
        return [];
    }

    public function extensionInfo(string $type): array
    {
        return [
            'title' => \ucfirst(\str_replace('-', ' ', $type)),
            'type' => $type,
            'description' => 'This field extension give support for '.$type. ' type field'
        ];
    }
}
