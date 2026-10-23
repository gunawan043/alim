<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        // Root adalah dashboard; tamu diarahkan ke login.
        $response->assertRedirect(route('login'));
    }
}
