<?php

namespace App\Exceptions;

use Exception;

class BlingDuplicateOrderException extends Exception
{
    protected $orderNumber;

    public function __construct(string $message = 'Pedido duplicado no Bling', string $orderNumber = '')
    {
        parent::__construct($message);
        $this->orderNumber = $orderNumber;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }
}
