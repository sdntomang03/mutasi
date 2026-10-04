<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_public_and_authentication_pages_are_available_to_guests(): void
    {
        $this->get('/')->assertOk()->assertSee('Temukan rekan');

        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk · Ruang Tukar Guru · DKI Jakarta', false)
            ->assertSee('Daftar');
        $this->get('/register')->assertOk()->assertSee('Ulangi kata sandi');
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Lupa kata sandi · Ruang Tukar Guru · DKI Jakarta', false)
            ->assertSee('Lupa kata sandi?');
    }
}
