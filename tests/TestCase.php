<?php

namespace Tests;

use App\Models\Customer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Helper para autenticar customer nos testes
     */
    protected function actingAsCustomer(Customer $customer): self
    {
        $token = $customer->createToken('test-token')->plainTextToken;
        
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

}
