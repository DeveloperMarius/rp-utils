<?php

namespace utils\router\rules;

use Somnambulist\Components\Validation\Rules\Date;

class BirthdayValidatorRule extends Date{

    protected string $message = ':attribute ist kein valides Geburtsdatum';

}