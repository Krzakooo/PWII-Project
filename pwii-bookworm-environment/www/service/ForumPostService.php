<?php

namespace Bookworm\service;

use PDO;

class ForumPostService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getForumPostsByForumId(int $forumId): ?array
    {
        $postStatement = $this->pdo->prepare('SELECT * FROM posts WHERE forum_id = :forum_id');
        $postStatement->execute(['forum_id' => $forumId]);
        $forumPosts = $postStatement->fetchAll(PDO::FETCH_ASSOC);

        return $forumPosts ?: null;
    }




    public function createForumPost(int $forumId, int $userId, array $postData): int
    {
        $content = $postData['content'];

        $sql = "INSERT INTO posts (forum_id, user_id, content) VALUES (:forum_id, :user_id, :content)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':forum_id', $forumId, \PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindParam(':content', $content, \PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

}
