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

    public function createRating(int $userId, int $bookId, int $rating): bool
    {
        $stmt = $this->db->prepare("
        INSERT INTO ratings (user_id, book_id, rating) 
        VALUES (:user_id, :book_id, :rating)
    ");
        $success = $stmt->execute([
            ':user_id' => $userId,
            ':book_id' => $bookId,
            ':rating' => $rating,
        ]);

        return $success;
    }


    public function createReview(int $userId, int $bookId, string $reviewText): bool
    {
        $sql = "INSERT INTO reviews (user_id, book_id, review_text) VALUES (:user_id, :book_id, :review_text)";
        $stmt = $this->db->prepare($sql);

        $success = $stmt->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
            'review_text' => $reviewText,
        ]);

        return $success;
    }



    public function saveReview($userId, $bookId, $review): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO reviews (user_id, book_id, review_text) 
            VALUES (:user_id, :book_id, :review_text) 
            ON DUPLICATE KEY UPDATE review_text = :review_text
        ");
        $stmt->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
            'review_text' => $review,
        ]);
    }

    public function deleteReview($userId, $bookId): void
    {
        $stmt = $this->db->prepare("DELETE FROM reviews WHERE user_id = :user_id AND book_id = :book_id");
        $stmt->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
        ]);
    }

    public function saveRating($userId, $bookId, $rating): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO ratings (user_id, book_id, rating) 
            VALUES (:user_id, :book_id, :rating) 
            ON DUPLICATE KEY UPDATE rating = :updated_rating
        ");
        $stmt->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
            'rating' => $rating,
            'updated_rating' => $rating,
        ]);
    }

    public function deleteRating($userId, $bookId): void
    {
        $stmt = $this->db->prepare("DELETE FROM ratings WHERE user_id = :user_id AND book_id = :book_id");
        $stmt->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
        ]);
    }

    public function getRatingByBookId($bookId)
    {
        $stmt = $this->db->prepare("SELECT rating FROM ratings WHERE book_id = :book_id");
        $stmt->execute(['book_id' => $bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewByBookId($bookId)
    {
        $stmt = $this->db->prepare("SELECT review_text FROM reviews WHERE book_id = :book_id");
        $stmt->execute(['book_id' => $bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
