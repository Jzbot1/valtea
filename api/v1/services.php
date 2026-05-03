<?php
// api/v1/services.php

require_once 'api_base.php';

$stmt = $db->query("SELECT s.id as service, s.name, c.name as category, s.selling_price as rate, s.min, s.max, s.type FROM services s JOIN categories c ON s.category_id = c.id WHERE s.status = 'active'");
$services = $stmt->fetchAll();

sendResponse($services);
