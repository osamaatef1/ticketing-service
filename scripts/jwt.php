<?php
/**
 * Generate a dev JWT for local testing of the ticketing service.
 *
 * Usage:
 *   php scripts/jwt.php <admin_id> [name] [email]
 * Example:
 *   php scripts/jwt.php 1 "Osama" "developers@intcore.com"
 *
 * Reads JWT_SECRET / JWT_ISSUER / JWT_AUDIENCE from your .env.
 */

require __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->safeLoad();

use Firebase\JWT\JWT;

$adminId = (int) ($argv[1] ?? 1);
$name    = $argv[2] ?? 'Local Dev';
$email   = $argv[3] ?? 'dev@local';

$secret  = $_ENV['JWT_SECRET']   ?? getenv('JWT_SECRET');
$issuer  = $_ENV['JWT_ISSUER']   ?? getenv('JWT_ISSUER')   ?: 'admin-service';
$audience= $_ENV['JWT_AUDIENCE'] ?? getenv('JWT_AUDIENCE') ?: 'ticketing-service';

if (!$secret) {
    fwrite(STDERR, "JWT_SECRET is not set in .env\n");
    exit(1);
}

$now = time();
$payload = [
    'iss'   => $issuer,
    'aud'   => $audience,
    'iat'   => $now,
    'exp'   => $now + 3600,
    'sub'   => $adminId,
    'name'  => $name,
    'email' => $email,
];

echo JWT::encode($payload, $secret, 'HS256').PHP_EOL;
