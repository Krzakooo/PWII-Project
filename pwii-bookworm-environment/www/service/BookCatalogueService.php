<?php

namespace Bookworm\service;

use Bookworm\model\Book;
use PDO;

class BookCatalogueService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function fetchBooks(): array
    {
        $stmt = $this->db->query("SELECT * FROM books");
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($books as $book) {
            $result[] = new Book(
                $book['title'],
                $book['author'],
                $book['description'],
                $book['pages'],
                $book['cover_url']
            );
        }
        return $result;
    }

    public function getBookDetails($bookId): ?Book
    {
        $stmt = $this->db->prepare("SELECT * FROM books WHERE id = :id");
        $stmt->execute(['id' => $bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($book) {
            return new Book(
                $book['title'],
                $book['author'],
                $book['description'],
                $book['pages'],
                $book['cover_url']
            );
        }
        return null;
    }

    public function saveBook(Book $book): void
    {
        $stmt = $this->db->prepare("INSERT INTO books (title, author, description, pages, cover_url) VALUES (:title, :author, :description, :pages, :cover_url)");
        $stmt->execute([
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'description' => $book->getDescription(),
            'pages' => $book->getPages(),
            'cover_url' => $book->getCoverUrl(),
        ]);
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

    public function handleImportForm($isbn): ?Book
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

        return new Book($title, $authorName, $description, $pages, $coverUrl);
    }
}
