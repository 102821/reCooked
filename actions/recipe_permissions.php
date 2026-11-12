<?php

declare(strict_types=1);

function can_manage_recipe(PDO $conn, int $userId, ?int $recipeOwnerId): bool
{
    $roleStatement = $conn->prepare('SELECT role FROM users WHERE id = :user_id');
    $roleStatement->execute(['user_id' => $userId]);

    return $roleStatement->fetchColumn() === 'admin' || $recipeOwnerId === $userId;
}
