<?php

namespace Bookworm\service;

use PDO;

class ForumService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllForums(): array
    {
        $statement = $this->pdo->query('SELECT * FROM forums');
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getForumById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM forums WHERE id = :id');
        $statement->execute(['id' => $id]);
        $forum = $statement->fetch(PDO::FETCH_ASSOC);
        return $forum ? $forum : null;
    }

    public function createForum(array $data): bool
    {
        if (isset($data['title'], $data['description'])) {
            $statement = $this->pdo->prepare('INSERT INTO forums (title, description) VALUES (:title, :description)');
            return $statement->execute([
                'title' => $data['title'],
                'description' => $data['description']
            ]);
        } else {
            return false;
        }
    }

    public function updateForum(int $id, array $data): bool
    {
        $statement = $this->pdo->prepare('UPDATE forums SET title = :title, description = :description WHERE id = :id');
        return $statement->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description']
        ]);
    }

    public function deleteForum(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM forums WHERE id = :id');
        if (!$statement->execute(['id' => $id])) {
            error_log('Issue deleting forum with ID: ' . $id);
            return false;
        }
        return true;
    }
}
