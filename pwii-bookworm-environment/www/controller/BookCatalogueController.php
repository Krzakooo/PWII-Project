<?php

namespace Bookworm\controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response as SlimResponse;
use Bookworm\service\TwigRenderer;
use Bookworm\service\BookCatalogueService;


class BookCatalogueController
{
    private $twig;
    private $service;

    public function __construct(TwigRenderer $twig, BookCatalogueService $service)
    {
        $this->twig = $twig;
        $this->service = $service;
    }

    public function showAddBookForm(Request $request, Response $response): Response
    {
        // Fetch books from both API and database
        $searchResults = $this->fetchBookSearchResults();
        $databaseBooks = $this->service->fetchBooks();

        // Combine search results with database books
        $allBooks = array_merge_recursive($searchResults, $databaseBooks);

        // Render Twig template with data
        $htmlContent = $this->twig->render('catalogue.twig', [
            'searchResults' => $allBooks
        ]);

        // Create a new response object
        $htmlResponse = new SlimResponse();
        $htmlResponse->getBody()->write($htmlContent);

        // Set content type header
        $htmlResponse = $htmlResponse->withHeader('Content-Type', 'text/html');

        return $htmlResponse;
    }

    private function fetchBookSearchResults()
    {

        // Hardcoded search strings for different categories
        $categories = ['action', 'adventure', 'mystery', 'fantasy', 'romance', 'science', 'history', 'biography', 'horror', 'comedy'];

        $allSearchResults = [];
        $allKeys = [];
        $maxBooksPerCategory = 20;
        $totalMaxBooks = 150;

        // Retrieve the current number of books in the database
        $currentBookCount = $this->service->getTotalBookCount();

        foreach ($categories as $category) {
            $category_url = "https://openlibrary.org/search.json?q={$category}&fields=title,author_name,cover_i,key,editions";

            $json = file_get_contents($category_url);
            $data = json_decode($json, true);

            $searchResults = [];

            if (isset($data['numFound']) && $data['numFound'] > 0) {
                foreach ($data['docs'] as $doc) {
                    if (count($searchResults) >= $maxBooksPerCategory) {
                        break; // Break the loop if maximum books per category is reached
                    }

                    if ($currentBookCount >= $totalMaxBooks) {
                        break 2;
                    }

                    $key_book = null;
                    if (isset($doc['editions']['docs'][0]['key'])) {
                        $key_book = $doc['editions']['docs'][0]['key'];
                    }

                    $book = [
                        'title' => $doc['title'],
                        'author_names' => $doc['author_name'] ?? ['Unknown'],
                        'cover_i' => $doc['cover_i'] ?? null,
                        'key_works' => $doc['key'] ?? null,
                        'key_book' => $key_book
                    ];

                    // Check if cover_i exists and fetch cover URL
                    $cover_url = '';
                    if ($book['cover_i']) {
                        $cover_url = "https://covers.openlibrary.org/b/id/{$book['cover_i']}-L.jpg";
                    }

                    // Add the key to the allKeys array if it's not already added
                    if ($book['key_works'] && !in_array($book['key_works'], $allKeys)) {
                        $allKeys[] = $book['key_works'];
                    }

                    // Check if the book already exists in the database
                    $bookId = $this->service->getBookId($book['title'], implode(', ', $book['author_names']));

                    // Save the book if it doesn't exist in the database
                    if (!$bookId) {
                        $description = $this->getBookDescription($book['key_works']);
                        $pages = $this->getBookPage($book['key_book']);

                        // Ensure description is a string
                        $description = is_string($description) ? $description : '';
                        $cover_url = is_string($cover_url) ? $cover_url : '';
                        $pages = is_int($pages) ? $pages : 0;

                        $bookId = $this->service->saveBook(
                            $book['title'],
                            implode(', ', $book['author_names']),
                            $description,
                            $pages,
                            $cover_url
                        );

                        // Increment the current book count
                        $currentBookCount++;
                    }
                    // Add book details to searchResults
                    $searchResults[] = [
                        'id' => $bookId,
                        'title' => $book['title'],
                        'author_names' => $book['author_names'],
                        'cover_url' => $cover_url,
                        'description' => $description ?? '',
                        'pages' => $pages ?? 0,

                    ];
                }
            }

            $allSearchResults[$category] = $searchResults;
        }

        return $allSearchResults;
    }

    private function getBookDescription($key)
    {
        $bookUrl = "https://openlibrary.org{$key}.json";
        $bookJson = file_get_contents($bookUrl);
        $bookData = json_decode($bookJson, true);

        if ($bookData !== null && isset($bookData['description'])) {
            return $bookData['description'];
        }

        return '';
    }

    private function getBookPage($key)
    {
        if ($key === null) {
            return 0;
        }

        $bookUrl = "https://openlibrary.org{$key}.json";
        $bookJson = file_get_contents($bookUrl);
        $bookData = json_decode($bookJson, true);

        if ($bookData !== null && isset($bookData['number_of_pages'])) {
            return $bookData['number_of_pages'];
        }

        return 0;
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
