<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 1/17/18
 * @package compass.blackmast.org
 */
require_once __DIR__ . '/../core.php';

use models\User;

/**
 * Masked readline. Useful for passwords
 * @param $prompt
 * @return string
 */
function readline_masked($prompt) {
    $result = trim(`/bin/bash -c "read -s -p '$prompt' result && echo \\\$result"`);
    echo "\n";
    return $result;
}

$user             = new User();
$user->name       = trim(readline('Name: '));
$user->email      = trim(readline('Email: '));
$user->perm_level = (int)trim(readline('Perm Level (int): '));

if ($user->perm_level === User::PERMLEVELS['Patron'])
    $user->client = trim(readline('Client: '));

$user->setPassword(readline_masked('Password: '));

$user->validate()->save();

echo "Done!\n";