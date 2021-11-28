<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

class Recaptcha extends Constraint
{
    public $message = 'Captcha invalide';
    public $action;

    public function __construct($action = null, string $message = null, array $groups = null, $payload = null, array $options = [])
    {
        if (\is_array($action)) {
            $options = array_merge($action, $options);
        } elseif (null !== $action) {
            $options['action'] = $action;
        }

        parent::__construct($options, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
