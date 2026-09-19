<?php

declare(strict_types=1);

it('serves the key as plain text', function () {
    $this->get('/abcdef1234567890.txt')
        ->assertOk()
        ->assertSee('abcdef1234567890', false)
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

it('does not serve other key files', function () {
    $this->get('/other-key-12345.txt')->assertNotFound();
});
