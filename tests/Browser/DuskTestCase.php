<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase as BaseDuskTestCase;

abstract class DuskTestCase extends BaseDuskTestCase
{
    use DatabaseMigrations;
}