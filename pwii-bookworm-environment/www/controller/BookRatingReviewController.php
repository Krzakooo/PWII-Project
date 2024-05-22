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

    public function __construct(TwigRenderer $twig, BookRatingReviewService $service)
    {
        $this->twig = $twig;
        $this->service = $service;
    }

    public function createRating(Request $request, Response $response, $args): Response
    {
        try {
            $userId = 1; // session should take care of this
            $bookId = $args['id'];
            $parsedBody = $request->getParsedBody();
            $rating = $parsedBody['rating'];

            $this->service->createRating($userId, $bookId, $rating);

            $responseData = [
                'message' => 'Rating created successfully'
            ];

            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($responseData));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');

            return $jsonResponse->withStatus(201);
        } catch (\Exception $e) {
            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode(['error' => 'An error occurred']));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');
            return $jsonResponse->withStatus(500);
        }
    }

    public function createReview(Request $request, Response $response, $args): Response
    {
        try {
            $data = $request->getParsedBody();

            $this->service->createReview($data);

            $responseData = [
                'message' => 'Review created successfully'
            ];

            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($responseData));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');

            return $jsonResponse->withStatus(201);
        } catch (\Exception $e) {
            return $jsonResponse->withStatus(500);
        }
    }


    public function getRatings(Request $request, Response $response, $args): Response
    {
        try {
            $bookId = $args['id'];
            $ratings = $this->service->getRatingByBookId($bookId);

            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($ratings));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');

            return $jsonResponse;
        } catch (\Exception $e) {
            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode(['error' => 'An error occurred']));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');
            return $jsonResponse->withStatus(500);
        }
    }

    public function getReviews(Request $request, Response $response, $args): Response
    {
        try {
            $bookId = $args['id'];
            $reviews = $this->service->getReviewByBookId($bookId);

            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($reviews));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');

            return $jsonResponse;
        } catch (\Exception $e) {
            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode(['error' => 'An error occurred']));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');
            return $jsonResponse->withStatus(500);
        }
    }


    public function rateBook(Request $request, Response $response, $args): Response
    {
        $userId = 1; // session should take care of this
        $bookId = $args['id'];
        $parsedBody = $request->getParsedBody();
        $rating = $parsedBody['rating'];

        $this->service->saveRating($userId, $bookId, $rating);

        $response->getBody()->write('Rating saved successfully');
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function deleteRating(Request $request, Response $response, $args): Response
    {
        $userId = 1; // session should take care of this
        $bookId = $args['id'];

        $this->service->deleteRating($userId, $bookId);

        $response->getBody()->write('Rating deleted successfully');
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function reviewBook(Request $request, Response $response, $args): Response
    {
        $userId = 1; // session should take care of this
        $bookId = $args['id'];
        $parsedBody = $request->getParsedBody();
        $review = $parsedBody['review'];

        $this->service->saveReview($userId, $bookId, $review);

        $response->getBody()->write('Review saved successfully');
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function deleteReview(Request $request, Response $response, $args): Response
    {
        $userId = 1; // session should take care of this
        $bookId = $args['id'];

        $this->service->deleteReview($userId, $bookId);

        $response->getBody()->write('Review deleted successfully');
        return $response->withHeader('Content-Type', 'text/html');
    }

}
