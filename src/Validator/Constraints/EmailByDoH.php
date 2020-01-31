<?php


namespace App\Validator\Constraints;


use Symfony\Component\Validator\Constraint;

class EmailByDoH extends Constraint
{
    public $message = 'This value is not a valid email address.';
    public $checkMX = true;
    public $checkHost = true;
}