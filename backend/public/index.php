
<?php
// KudoPixel Modular Backend - public/index.php
// The one and only gate to our glorious empire!

/**
 * Welcome to the KudoVerse Backend.
 *
 * All requests, from all corners of the internet, enter through this single file.
 * This is a core principle of modern web application security and design.
 *
 * This file does only two things:
 * 1. Fire up the main engine (bootstrap).
 * 2. Tell the engine to handle the request.
 *
 * No business logic should ever be placed here.
 */

// Step 1: Fire up the engine.
require_once __DIR__ . '/../core/bootstrap.php';

// Step 2: Tell the engine to handle the incoming request.
// We pass the $pdo object which was created in bootstrap.php
handle_request($pdo);

?>
