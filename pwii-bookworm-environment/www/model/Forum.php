<?php

namespace Bookworm\model;

class Forum
{
    private int $id;
    private string $title;
    private string $description;
    private array $posts;

    public function __construct(int $id, string $title, string $description, array $posts)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->posts = $posts;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPosts(): array
    {
        return $this->posts;
    }

    // Setters
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function addPost(Post $post): void
    {
        $this->posts[] = $post;
    }

    public function setPosts(array $posts): void
    {
        $this->posts[] = $posts;
    }

}
