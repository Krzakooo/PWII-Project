<?php

namespace Bookworm\service;

use PDO;

class BookCatalogueService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getBookId($title, $author)
    {
        $query = "SELECT id FROM books WHERE title = :title AND author = :author LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':author', $author);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['id'] : null;
    }

    public function getTotalBookCount()
    {
        $query = "SELECT COUNT(*) as total FROM books";
        $result = $this->db->query($query);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        return (int)$row['total'];
    }


    public function saveBook(string $title, string $author, string $description, int $pages, string $cover): ?int
    {
        $sql = "INSERT INTO books (title, author, description, pages, cover) VALUES (:title, :author, :description, :pages, :cover)";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'title' => $title,
            'author' => $author,
            'description' => $description,
            'pages' => $pages,
            'cover' => $cover,
        ]);

        if ($success) {
            return $this->db->lastInsertId();
        } else {
            return null;
        }
    }

    public function addBookToCatalogue(array $data): bool
    {
        if (isset($data['title'], $data['author'], $data['description'], $data['pages'])) {
            $insertStatement = $this->db->prepare("INSERT INTO books (title, author, description, pages, cover) VALUES (:title, :author, :description, :pages, :cover)");

            $insertStatement->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $insertStatement->bindParam(':author', $data['author'], PDO::PARAM_STR);
            $insertStatement->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $insertStatement->bindParam(':pages', $data['pages'], PDO::PARAM_INT);
            $insertStatement->bindParam(':cover', $data['cover'], PDO::PARAM_STR);

            return $insertStatement->execute();
        } else {
            return false;
        }
    }

    public function getBookDetails($bookId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM books WHERE id = :id");
        $stmt->execute(['id' => $bookId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    public function updateBookDetails($bookId, $title, $author, $description, $pages, $cover)
    {
        $sql = "UPDATE books SET title = :title, author = :author, description = :description, pages = :pages, cover = :cover WHERE id = :bookId";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'title' => $title,
            'author' => $author,
            'description' => $description,
            'pages' => $pages,
            'cover' => $cover,
            'bookId' => $bookId,
        ]);

        return $success;
    }


}
