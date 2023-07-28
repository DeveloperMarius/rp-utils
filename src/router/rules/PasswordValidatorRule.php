<?php

namespace utils\router\rules;

use Somnambulist\Components\Validation\Rule;

class PasswordValidatorRule extends Rule{

    protected string $message = 'rule.password';
    protected array $fillableParams = ['password_type'];

    public function check($value): bool
    {
        $key = $this->parameter('password_type', 'normal');
        $type = PasswordType::tryFrom($key) ?? PasswordType::SECURITY_NORMAL;
        return $type->validate($value->getValue());
    }

}