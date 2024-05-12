<?php

namespace Bookworm\Model;

class Forum
{
    private int $id;
    private string $title;
    private string $description;

    public function __construct(int $id, string $title, string $description)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
    }

    // Getters
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

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

}
