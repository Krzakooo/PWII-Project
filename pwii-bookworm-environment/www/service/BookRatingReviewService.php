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


    public function createRating($userId, $bookId, $rating): void
    {
        $stmt = $this->db->prepare("
        INSERT INTO ratings (user_id, book_id, rating) 
        VALUES (:user_id, :book_id, :rating)
    ");
        $stmt->execute([
            'user_id' => $userId,
            'book_id' => $bookId,
            'rating' => $rating,
        ]);
    }

    public function createReview(array $reviewData): bool
    {
        if (isset($reviewData['user_id'], $reviewData['book_id'], $reviewData['review_text'])) {
            $stmt = $this->db->prepare("
            INSERT INTO reviews (user_id, book_id, review_text) 
            VALUES (:user_id, :book_id, :review_text)
        ");

            return $stmt->execute([
                'user_id' => $reviewData['user_id'],
                'book_id' => $reviewData['book_id'],
                'review_text' => $reviewData['review_text'],
            ]);
        } else {
            return false;
        }
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
        return $stmt->fetch(PDO::FETCH_ASSOC)['review_text'];
    }
}
