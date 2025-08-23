<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\ExceptionHandlerTrait;

abstract class Controller
{
    use ApiResponse;
    use ExceptionHandlerTrait;
}
