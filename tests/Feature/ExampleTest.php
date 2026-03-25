<?php

it('redirects guests from the application root to login', function (): void {
    $this->get('/')
        ->assertRedirect('/login');
});
