<?php

namespace Simp\Core\modules\structures\content_types\field;

use Symfony\Component\HttpFoundation\Request;

interface FieldBuilderInterface
{
    public function build(Request $request): string;
    public function fieldArray(Request $request): array;

}