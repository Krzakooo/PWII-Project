<?php

namespace Bookworm\model;

class Book
{
    private $title;
    private $author;
    private $description;
    private $pages;
    private $cover;

    public function __construct($title, $author, $description, $pages, $cover = null)
    {
        $this->title = $title;
        $this->author = $author;
        $this->description = $description;
        $this->pages = $pages;
        $this->cover = $cover;
    }

    // Getters and setters
    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function setPages($pages)
    {
        $this->pages = $pages;
    }

    public function getCover()
    {
        return $this->cover;
    }

    public function setCover($cover)
    {
        $this->cover = $cover;
    }

    // Additional methods for handling file upload, validation, etc.
    // You can add methods here for processing file upload, validation, etc.
}
