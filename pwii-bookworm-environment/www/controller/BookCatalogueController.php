<?php

namespace Bookworm\controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response as SlimResponse;
use Slim\Views\Twig;
use Bookworm\service\TwigRenderer;
use Bookworm\service\BookCatalogueService;
use Bookworm\model\Book;

class BookCatalogueController
{
    private $twig;
    private $service;

    public function __construct(TwigRenderer $twig, BookCatalogueService $service)
    {
        $this->twig = $twig;
        $this->service = $service;
    }


    private function fetchBookSearchResults()
    {
        // Hardcoded search strings for different categories
        $categories = ['action', 'adventure', 'mystery', 'fantasy', 'romance', 'science', 'history', 'biography', 'horror', 'comedy'];

        $allSearchResults = [];

        foreach ($categories as $category) {
            $url = "https://openlibrary.org/search.json?q={$category}&fields=title,author_name,cover_i";

            $json = file_get_contents($url);

            $data = json_decode($json, true);

            $searchResults = [];

            if (isset($data['numFound']) && $data['numFound'] > 0) {
                foreach ($data['docs'] as $doc) {
                    $book = [
                        'title' => $doc['title'],
                        'author_names' => $doc['author_name'] ?? ['Unknown'],
                        'cover_i' => $doc['cover_i'] ?? null
                    ];

                    $cover_url = null;

                    // Check if cover_i exists and fetch cover URL
                    if ($book['cover_i']) {
                        $cover_url = "https://covers.openlibrary.org/b/id/{$book['cover_i']}-L.jpg";
                    }

                    // Check if the book already exists in the database
                    $bookId = $this->service->getBookId($book['title'], implode(', ', $book['author_names']));

                    if (!$bookId) {
                        // Save the book if it doesn't exist
                        $bookId = $this->service->saveBook(
                            $book['title'],
                            implode(', ', $book['author_names']),
                            '',
                            0,
                            $cover_url
                        );
                    }

                    // Add book to search results
                    $searchResults[] = [
                        'id' => $bookId,
                        'title' => $book['title'],
                        'author_names' => $book['author_names'],
                        'cover_url' => $cover_url
                    ];
                }
            }

            $allSearchResults[$category] = $searchResults;
        }

        return $allSearchResults;
    }


    public function showAddBookForm(Request $request, Response $response): Response
    {
        $books = $this->service->fetchBooks();
        $searchResults = $this->fetchBookSearchResults();

        session_start();

        $isLoggedIn = isset($_SESSION['user_id']);


        // Render Twig template with data
        $htmlContent = $this->twig->render('catalogue.twig', [
            'books' => $books,
            'searchResults' => $searchResults,
            'isLoggedIn' => $isLoggedIn,
        ]);

        // Create a new response object
        $htmlResponse = new SlimResponse();
        $htmlResponse->getBody()->write($htmlContent);

        // Set content type header
        $htmlResponse = $htmlResponse->withHeader('Content-Type', 'text/html');

        return $htmlResponse;
    }

    public function addBookToCatalogue(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            $bookId = $this->service->addBookToCatalogue($data);

            if ($bookId !== null) {
                $responseData = ['message' => 'Book created successfully', 'book_id' => $bookId];
                $statusCode = 201;
            } else {
                $responseData = ['error' => 'Failed to create book'];
                $statusCode = 400;
            }

            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($responseData));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');

            return $jsonResponse->withStatus($statusCode);
        } catch (\Exception $e) {
            return $response->withStatus(500);
        }
    }

    public function getBookDetails(Request $request, Response $response, $args): Response
    {
        $authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

        if (!$authenticated) {
            return $response->withHeader('Location', '/sign-in')->withStatus(302);
        }

        $bookId = $args['id'];
        $bookDetails = $this->service->getBookDetails($bookId);
        $searchResults = $this->fetchBookSearchResults();
        session_start();

        $isLoggedIn = isset($_SESSION['user_id']);

        $response->getBody()->write($this->twig->render('book_details.twig', [
            'book' => $bookDetails,
            'searchResults' => $searchResults,
            'isLoggedIn' => $isLoggedIn
        ]));
        $jsonResponse = new SlimResponse();
        $jsonResponse->getBody()->write(json_encode($response));
        $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');
        return $jsonResponse;
    }


    public function rateBook(Request $request, Response $response, $args): Response
    {
        $bookId = $args['id'];
        $parsedBody = $request->getParsedBody();
        $rating = $parsedBody['rating'];

        $this->service->saveRating($bookId, $rating);

        $response->getBody()->write('Rating saved successfully');
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function deleteRating(Request $request, Response $response, $args): Response
    {
        $bookId = $args['id'];

        $this->service->deleteRating($bookId);

        $response->getBody()->write('Rating deleted successfully');
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function reviewBook(Request $request, Response $response, $args): Response
    {
        $bookId = $args['id'];
        $parsedBody = $request->getParsedBody();
        $review = $parsedBody['review'];

        $this->service->saveReview($bookId, $review);

        $response->getBody()->write('Review saved successfully');
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function deleteReview(Request $request, Response $response, $args): Response
    {
        $bookId = $args['id'];

        $this->service->deleteReview($bookId);

        $response->getBody()->write('Review deleted successfully');
        return $response->withHeader('Content-Type', 'text/html');
    }

    /* //Search funktion to seach for category till books
     public function getBookSearchResultsJSON($request, $response, $args)
    {
        $searchString = urlencode($args['id']);

        $url = "https://openlibrary.org/search.json?q={$searchString}&fields=title,author_name";

        $json = file_get_contents($url);

        // Decode JSON data
        $data = json_decode($json, true);

        if (isset($data['numFound']) && $data['numFound'] > 0) {
            $searchResults = [];

            foreach ($data['docs'] as $doc) {
                $book = [
                    'title' => $doc['title'],
                    'author_names' => $doc['author_name'] ?? ['Unknown']
                ];
                $searchResults[] = $book;
            }

            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($searchResults));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');
            return $jsonResponse;
        }

        // If no matching book found, return empty array
        $emptyResponse = new SlimResponse();
        $emptyResponse->getBody()->write(json_encode([]));
        $emptyResponse = $emptyResponse->withHeader('Content-Type', 'application/json');
        return $emptyResponse;
    }*/
}
