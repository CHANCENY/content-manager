<?php

namespace Simp\Core\modules\structures\taxonomy;

class VocabularyManager
{
    public function addVocabulary(string $name)
    {

    }

    public static function factory(): VocabularyManager
    {
        return new self();
    }
}