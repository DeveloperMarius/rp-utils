<?php

namespace utils\router\rules;

use Somnambulist\Components\Validation\Rules\Regex;

class ZipValidatorRule extends Regex{

    protected string $message = 'rule.zip';

    public function check($value): bool
    {
        $this->fillParameters(array(
            'regex' => '/^([0]{1}[1-9]{1}|[1-9]{1}[0-9]{1})[0-9]{3}$/'
        ));
        return parent::check($value);
    }

}