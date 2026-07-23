<?php

declare(strict_types=1);

test('the application boots and serves the welcome page', function (): void {
    $response = $this->get('/');

    $response->assertStatus(200);
});
