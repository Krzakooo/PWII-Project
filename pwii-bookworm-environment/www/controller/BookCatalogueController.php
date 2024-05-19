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
            $url = "https://openlibrary.org/search.json?q={$category}&fields=title,author_name";

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

                    // Check if the book already exists in the database
                    if (!$this->service->bookExists($book['title'], implode(', ', $book['author_names']))) {
                        // Save book to the database
                        $bookId = $this->service->saveBook(
                            $book['title'],
                            implode(', ', $book['author_names']),
                            '',
                            0,
                            ''
                        );

                        // If book is saved successfully, add it to search results
                        if ($bookId !== null) {
                            $searchResults[] = [
                                'id' => $bookId,
                                'title' => $book['title'],
                                'author_names' => $book['author_names']
                            ];
                        }
                    }

                    // Limit to 5 books per category
                    if (count($searchResults) >= 5) {
                        break;
                    }
                }

                $allSearchResults[$category] = $searchResults;
            } else {
                $allSearchResults[$category] = [];
            }
        }

        return $allSearchResults;
    }


    public function showAddBookForm(Request $request, Response $response): Response
    {
        $books = $this->service->fetchBooks();
        $searchResults = $this->fetchBookSearchResults();

        // Render Twig template with data
        $htmlContent = $this->twig->render('catalogue.twig', [
            'books' => $books,
            'searchResults' => $searchResults,
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
        $bookId = $args['id'];
        $bookDetails = $this->service->getBookDetails($bookId);
        $searchResults = $this->fetchBookSearchResults();

        $response->getBody()->write($this->twig->render('book_details.twig', [
            'book' => $bookDetails,
            'searchResults' => $searchResults
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
