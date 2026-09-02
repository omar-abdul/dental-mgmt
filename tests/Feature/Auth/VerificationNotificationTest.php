<?php

test('verification notification route is not available', function () {
    $this->post('/email/verification-notification')->assertNotFound();
});
