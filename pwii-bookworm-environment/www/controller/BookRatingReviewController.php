<?php

namespace Bookworm\controller;

use Bookworm\service\BookRatingReviewService;
use Bookworm\service\TwigRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response as SlimResponse;

class BookRatingReviewController
{
    private $twig;
    private $service;
    private $authController;

    public function __construct(TwigRenderer $twig, BookRatingReviewService $service, AuthController $authController)
    {
        $this->twig = $twig;
        $this->service = $service;
        $this->authController = $authController;
    }


    public function getRatings(Request $request, Response $response, $args): Response
    {
        try {
            $bookId = $args['id'];
            $ratings = $this->service->getRatingByBookId($bookId);
            $response->getBody()->write(json_encode($ratings));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $errorResponse = ['error' => 'An error occurred'];
            $response->getBody()->write(json_encode($errorResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getReviews(Request $request, Response $response, $args): Response
    {
        try {
            $bookId = $args['id'];
            $reviews = $this->service->getReviewByBookId($bookId);
            $response->getBody()->write(json_encode($reviews));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $errorResponse = ['error' => 'An error occurred'];
            $response->getBody()->write(json_encode($errorResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function rateBook(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $this->authController->getUserIdFromSession();
            $bookId = $args['id'];
            $parsedBody = $request->getParsedBody();
            $rating = $parsedBody['rating'];

            $this->service->saveRating($userId, $bookId, $rating);

            $responseData = ['message' => 'Rating saved successfully'];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $errorResponse = ['error' => 'An error occurred'];
            $response->getBody()->write(json_encode($errorResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function deleteRating(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $this->authController->getUserIdFromSession();
            $bookId = $args['id'];

            $this->service->deleteRating($userId, $bookId);

            $responseData = ['message' => 'Rating deleted successfully'];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $errorResponse = ['error' => 'An error occurred'];
            $response->getBody()->write(json_encode($errorResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function reviewBook(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $this->authController->getUserIdFromSession();
            $bookId = $args['id'];
            $parsedBody = $request->getParsedBody();
            $review = $parsedBody['review'];

            $success = $this->service->createReview($userId, $bookId, $review);

            if ($success) {
                $responseData = [
                    'message' => 'Review created successfully',
                    'userId' => $userId,
                    'bookId' => $bookId,
                    'review' => $review
                ];
                $statusCode = 201;
            } else {
                $responseData = ['error' => 'Failed to create review'];
                $statusCode = 400;
            }

            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($responseData));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');

            return $jsonResponse->withStatus($statusCode);
        } catch (\Exception $e) {
            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode(['error' => 'An error occurred']));
            return $jsonResponse->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

    }

    public function deleteReview(Request $request, Response $response, $args): Response
    {
        try {
            $userId = $this->authController->getUserIdFromSession();
            $bookId = $args['id'];

            $this->service->deleteReview($userId, $bookId);

            $responseData = ['message' => 'Review deleted successfully'];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $errorResponse = ['error' => 'An error occurred'];
            $response->getBody()->write(json_encode($errorResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
