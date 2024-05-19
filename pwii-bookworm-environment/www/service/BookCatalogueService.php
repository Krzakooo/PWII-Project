<?php

namespace Bookworm\service;

use PDO;

class BookCatalogueService
{
    private $db;
    const OPENLIBRARY_ISBN_URL = 'https://openlibrary.org/isbn/';
    const OPENLIBRARY_WORKS_URL = 'https://openlibrary.org/works/';
    const OPENLIBRARY_AUTHORS_URL = 'https://openlibrary.org/authors/';
    const OPENLIBRARY_COVER_URL = 'https://covers.openlibrary.org/b/id/';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    private function fetchData($url)
    {
        $response = file_get_contents($url);
        return $response ? json_decode($response, true) : null;
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

    public function fetchBooks(): ?array
    {
        $stmt = $this->db->query("SELECT * FROM books");
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $books ?: null;
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

    public function getBookRatings($bookId): array
    {
        $stmt = $this->db->prepare("SELECT rating FROM ratings WHERE book_id = :book_id");
        $stmt->execute(['book_id' => $bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookReviews($bookId): array
    {
        $stmt = $this->db->prepare("SELECT review FROM reviews WHERE book_id = :book_id");
        $stmt->execute(['book_id' => $bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveRating($bookId, $rating): void
    {
        $stmt = $this->db->prepare("INSERT INTO ratings (book_id, rating) VALUES (:book_id, :rating) ON DUPLICATE KEY UPDATE rating = :rating");
        $stmt->execute([
            'book_id' => $bookId,
            'rating' => $rating,
        ]);
    }

    public function deleteRating($bookId): void
    {
        $stmt = $this->db->prepare("DELETE FROM ratings WHERE book_id = :book_id");
        $stmt->execute(['book_id' => $bookId]);
    }

    public function saveReview($bookId, $review): void
    {
        $stmt = $this->db->prepare("INSERT INTO reviews (book_id, review) VALUES (:book_id, :review) ON DUPLICATE KEY UPDATE review = :review");
        $stmt->execute([
            'book_id' => $bookId,
            'review' => $review,
        ]);
    }

    public function deleteReview($bookId): void
    {
        $stmt = $this->db->prepare("DELETE FROM reviews WHERE book_id = :book_id");
        $stmt->execute(['book_id' => $bookId]);
    }

    public function handleImportForm($isbn): ?array
    {
        $url = "https://openlibrary.org/isbn/{$isbn}.json";
        $response = file_get_contents($url);

        if ($response === false) {
            return null;
        }

        $bookData = json_decode($response, true);

        $title = $bookData['title'];
        $pages = $bookData['number_of_pages'] ?? 'N/A';
        $workIdentifier = $bookData['works'][0]['key'];
        $coverId = $bookData['covers'][0] ?? null;

        $workUrl = "https://openlibrary.org{$workIdentifier}.json";
        $workResponse = file_get_contents($workUrl);
        $workData = json_decode($workResponse, true);
        $description = $workData['description']['value'] ?? 'No description available';

        $authorIdentifier = $workData['authors'][0]['key'];
        $authorUrl = "https://openlibrary.org{$authorIdentifier}.json";
        $authorResponse = file_get_contents($authorUrl);
        $authorData = json_decode($authorResponse, true);
        $authorName = $authorData['name'] ?? 'Unknown';

        $coverUrl = $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg" : 'No cover available';

        return [
            'title' => $title,
            'author' => $authorName,
            'description' => $description,
            'pages' => $pages,
            'cover' => $coverUrl,
        ];
    }


}
