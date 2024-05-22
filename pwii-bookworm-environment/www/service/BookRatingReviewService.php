<?php

namespace Bookworm\service;

use PDO;

class BookRatingReviewService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
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

    public function getReviewByBookId($bookId): ?string
    {
        $stmt = $this->db->prepare("SELECT review FROM reviews WHERE book_id = :book_id");
        $stmt->execute(['book_id' => $bookId]);
        $review = $stmt->fetchColumn();
        return $review !== false ? $review : null;
    }

    public function getRatingByBookId($bookId): ?int
    {
        $stmt = $this->db->prepare("SELECT rating FROM ratings WHERE book_id = :book_id");
        $stmt->execute(['book_id' => $bookId]);
        $rating = $stmt->fetchColumn();
        return $rating !== false ? (int)$rating : null;
    }

}