<?php

date_default_timezone_set("Europe/Berlin");

if(class_exists('\Pecee\SimpleRouter\Route\Route')){
    \Pecee\SimpleRouter\Route\Route::$parameterReverseOrder = false;
    \Pecee\Http\Input\InputValidator::$parseAttributes = true;

    \Pecee\Http\Input\InputValidator::getFactory()->messages()->default('de');
    \Pecee\Http\Input\InputValidator::getFactory()->addRule('birthday', new \utils\router\rules\BirthdayValidatorRule());
    \Pecee\Http\Input\InputValidator::getFactory()->addRule('model', new \utils\router\rules\ModelValidatorRule());
    \Pecee\Http\Input\InputValidator::getFactory()->addRule('password', new \utils\router\rules\PasswordValidatorRule());
    \Pecee\Http\Input\InputValidator::getFactory()->addRule('phone', new \utils\router\rules\PhoneValidatorRule());
    \Pecee\Http\Input\InputValidator::getFactory()->addRule('zip', new \utils\router\rules\ZipValidatorRule());
    \Pecee\Http\Input\InputValidator::getFactory()->registerLanguageMessages('de');
    \Pecee\Http\Input\InputValidator::getFactory()->messages()->add('de', array(
        'rule.birthday' => ':attribute ist kein valides Geburtsdatum',
        'rule.model' => ':attribute existiert nicht',
        'rule.password' => ':attribute entspricht nicht den Passwort anforderungen.',
        'rule.phone' => ':attribute ist keine valide Deutsche Telefonnummer',
        'rule.zip' => ':attribute ist keine valide Deutsche Postleitzahl'
    ));
}
