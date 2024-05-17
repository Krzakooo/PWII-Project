<?php

namespace Bookworm\model;

class Post
{
    private int $id;
    private int $forumId;
    private int $userId;
    private string $content;


    public function __construct(int $id, int $forumId, int $userId, string $content)
    {
        $this->id = $id;
        $this->forumId = $forumId;
        $this->userId = $userId;
        $this->content = $content;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getForumId(): int
    {
        return $this->forumId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getContent(): string
    {
        return $this->content;
    }


}
