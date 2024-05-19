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
        if (isset($postData['forum_id'], $postData['user_id'], $postData['content'])) {
            $postStatement = $this->pdo->prepare("INSERT INTO posts (forum_id, user_id, content) VALUES (:forumId, :userId, :content)");

            $postStatement->bindParam(':forumId', $postData['forum_id'], PDO::PARAM_INT);
            $postStatement->bindParam(':userId', $postData['user_id'], PDO::PARAM_INT);
            $postStatement->bindParam(':content', $postData['content'], PDO::PARAM_STR);

            return $postStatement->execute();
        } else {
            return false;
        }
    }



}
