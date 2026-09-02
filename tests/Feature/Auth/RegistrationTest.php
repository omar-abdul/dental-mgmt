<?php

test('registration screen is not available', function () {
    $this->get('/register')->assertNotFound();
});

test('registration is not available', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ])->assertNotFound();
});
