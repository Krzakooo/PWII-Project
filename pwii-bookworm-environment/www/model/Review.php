<?php
namespace Bookworm\model;

class Review
{
    private $user_id;
    private $book_id;
    private $review_text;
    private $created_at;
    private $updated_at;

    public function __construct($user_id, $book_id, $review_text, $created_at, $updated_at)
    {
        $this->user_id = $user_id;
        $this->book_id = $book_id;
        $this->review_text = $review_text;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getBookId()
    {
        return $this->book_id;
    }

    public function getReviewText()
    {
        return $this->review_text;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

}
