<?php

namespace utils\router\rules;

use Somnambulist\Components\Validation\Rules\Regex;

class PhoneValidatorRule extends Regex{

    protected string $message = 'rule.phone';

    public function check($value): bool
    {
        $this->fillParameters(array(
            'regex' => '/^(1[567]\d)[ ]?(\d\d\d\d\d\d\d\d?)$/'
        ));
        return parent::check($value);
    }

}