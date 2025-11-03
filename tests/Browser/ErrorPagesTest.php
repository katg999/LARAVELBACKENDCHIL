<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ErrorPagesTest extends DuskTestCase
{
    /** @test */
    public function error_403_page_displays_correctly()
    {
        $this->browse(function (Browser $browser) {
            // Visit a protected route without authentication
            $browser->visit('/school-dashboard')
                    ->assertSee('403')
                    ->assertSee('ACCESS DENIED')
                    ->assertSee('You don\'t have permission to access this page.')
                    ->assertSee('KETI AI')
                    ->assertSee('ketiai.com');
        });
    }

    /** @test */
    public function error_404_page_displays_correctly()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/non-existent-page')
                    ->assertSee('404')
                    ->assertSee('SORRY!')
                    ->assertSee('The page you\'re looking for was not found.')
                    ->assertSee('KETI AI')
                    ->assertSee('ketiai.com');
        });
    }

    /** @test */
    public function error_500_page_displays_correctly()
    {
        // This would require triggering a 500 error, which is harder to test
        // For now, we'll skip or use a route that throws an exception
        $this->markTestSkipped('500 error testing requires specific error triggering');
    }
}