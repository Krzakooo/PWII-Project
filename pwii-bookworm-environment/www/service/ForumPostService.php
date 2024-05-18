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


    public function createForumPost(array $postData): bool
    {
        try {
            $sql = "INSERT INTO posts (forum_id, user_id, content) VALUES (:forumId, :userId, :content)";
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindParam(':forumId', $postData['forum_id'], PDO::PARAM_INT);
            $stmt->bindParam(':userId', $postData['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':content', $postData['content'], PDO::PARAM_STR);

            if ($stmt->execute()) {
                return true;
            } else {
                error_log('Statement execution failed: ' . implode(', ', $stmt->errorInfo()));
                return false;
            }
        } catch (\PDOException $e) {
            error_log('PDOException: ' . $e->getMessage());
            return false;
        }
    }

}
