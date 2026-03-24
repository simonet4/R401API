<?php

namespace R301\Vue\Component;

class SelectPerformance extends Select {

    public function __construct(
            ?string $description,
            ?string $selectedValue = null
    ) {
        $values = [
            'EXCELLENTE' => 'EXCELLENTE',
            'BONNE' => 'BONNE',
            'MOYENNE' => 'MOYENNE',
            'MAUVAISE' => 'MAUVAISE',
            'CATASTROPHIQUE' => 'CATASTROPHIQUE',
        ];

        parent::__construct($values, "performance", $description, $selectedValue);
    }
}