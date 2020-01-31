<?php


namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\EmailByDoHValidator;
use PHPUnit\Framework\TestCase;

class EmailByDoHValidatorTest extends TestCase
{
    public function testValidMx()
    {
        $host = 'gmail.com';
        $constraintEmailValidator = new EmailByDoHValidator();
        $ret = $constraintEmailValidator->checkMXByDoH($host);
        $this->assertTrue($ret);
    }

    public function testInvalidMx()
    {
        $host = 'x1hirotae.com';
        $constraintEmailValidator = new EmailByDoHValidator();
        $ret = $constraintEmailValidator->checkMXByDoH($host);
        $this->assertFalse($ret);
    }

    public function testValidHost()
    {
        $host = 'gmail.com';
        $constraintEmailValidator = new EmailByDoHValidator();
        $ret = $constraintEmailValidator->checkHostByDoH($host);
        $this->assertTrue($ret);
    }

    public function testInvalidHost()
    {
        $host = 'x1hirotae.com';
        $constraintEmailValidator = new EmailByDoHValidator();
        $ret = $constraintEmailValidator->checkHostByDoH($host);
        $this->assertFalse($ret);
    }
}