<?php

namespace R301\Vue\Component;

class SelectResultat extends Select {
    public function __construct(
        ?string $description,
        ?string $selectedValue = null
    ) {
        $values = [
            'VICTOIRE' => 'VICTOIRE',
            'DEFAITE' => 'DEFAITE',
            'NUL' => 'NUL',
        ];

        parent::__construct($values, "resultat", $description, $selectedValue);
    }
}