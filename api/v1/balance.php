<?php
// api/v1/balance.php

require_once 'api_base.php';

sendResponse([
    'status' => 'success',
    'balance' => (float) $user_balance,
    'currency' => 'INR'
]);
