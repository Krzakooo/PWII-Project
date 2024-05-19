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

    public function getBookSearchResultsJSON($request, $response, $args)
    {
        $searchString = urlencode($args['id']);

        $url = "https://openlibrary.org/search.json?q={$searchString}&fields=title,author_name";

        $json = file_get_contents($url);

        // Decode JSON data
        $data = json_decode($json, true);

        // Check if there are any search results
        if ($data['numFound'] > 0) {
            $searchResults = [];
            // Loop through each document
            foreach ($data['docs'] as $doc) {
                // Add title and author names to search results array
                $book = [
                    'title' => $doc['title'],
                    'author_names' => $doc['author_name']
                ];
                $searchResults[] = $book;
            }
            // Create a new response with JSON data
            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($searchResults));
            // Set content type to application/json
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');
            return $jsonResponse;
        }

        // If no matching book found, return empty array
        $emptyResponse = new SlimResponse();
        $emptyResponse->getBody()->write(json_encode([]));
        $emptyResponse = $emptyResponse->withHeader('Content-Type', 'application/json');
        return $emptyResponse;
    }



    public function handleGetCatalogue(Response $response)
    {
        $this->authenticate();
        $books = $this->fetchAllBooks();

        $response->getBody()->write(json_encode($books));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function handlePostCatalogue(Request $request)
    {
        $this->authenticate();

        $parsedBody = $request->getParsedBody();

        if (isset($parsedBody['isbn'])) {
            // Handle adding book by ISBN
            $isbn = $parsedBody['isbn'];
            $bookDetails = $this->service->fetchBookDetailsByISBN($isbn);

            if ($bookDetails) {
                $book = new Book($bookDetails['title'], $bookDetails['author'], $bookDetails['description'], $bookDetails['pages'], $bookDetails['cover']);
                $this->saveBook($book);
            }

        }
    }


    public function handleGetAllBooks()
    {
        $books = $this->fetchAllBooks();

        $booksArray = array_map(function ($book) {
            return [
                'title' => $book->getTitle(),
                'author' => $book->getAuthor(),
                'description' => $book->getDescription(),
                'pages' => $book->getPages(),
                'cover' => $book->getCover()
            ];
        }, $books);

        header('Content-Type: application/json');
        echo json_encode($booksArray);
    }

    private function authenticate()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /signin');
            exit;
        }
    }

    private function validateBook(Book $book)
    {
        if (!$book->getTitle() || !$book->getAuthor() || !$book->getDescription() || !$book->getPages()) {
            throw new \Exception('All required fields must be provided.');
        }
    }


    public function showAddBookForm(Request $request, Response $response): Response
    {

        $books = $this->service->fetchBooks();

        $response->getBody()->write($this->twig->render('catalogue.twig', [

            'books' => $books,
        ]));
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function searchBooks($query): array
    {
        $url = "https://openlibrary.org/search.json?q={$query}&fields=key,title,author_name,editions";

        $response = file_get_contents($url);

        if ($response === false) {
            return [];
        }

        $responseData = json_decode($response, true);

        $books = [];
        foreach ($responseData['docs'] as $doc) {
            $title = $doc['title'] ?? 'Unknown';
            $author = implode(', ', $doc['author_name']) ?? 'Unknown';
            $description = $doc['edition_count'] ?? 'N/A';
            $pages = $doc['edition_count'] ?? 'N/A';

            $bookKey = str_replace('/works/', '', $doc['key']);
            $bookUrl = "https://openlibrary.org/works/{$bookKey}.json";
            $bookResponse = file_get_contents($bookUrl);
            $bookData = json_decode($bookResponse, true);

            $coverUrl = null;
            if (isset($bookData['covers'][0])) {
                $coverId = $bookData['covers'][0];
                $coverUrl = "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg";
            }

            $books[] = new Book($title, $author, $description, $pages, $coverUrl);
        }

        return $books;
    }


    public function addBookToCatalogue(Request $request, Response $response): Response
    {
        $authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

        if (!$authenticated) {
            $response->getBody()->write($this->twig->render('signin.twig'));
            return $response->withHeader('Content-Type', 'text/html');
        }

        $parsedBody = $request->getParsedBody();
        if (isset($parsedBody['isbn'])) {
            // Handle import form submission
            $book = $this->service->handleImportForm($parsedBody['isbn']);
        } else {
            // Handle full form submission
            $book = new Book(
                $parsedBody['title'],
                $parsedBody['author'],
                $parsedBody['description'],
                $parsedBody['pages'],
                $parsedBody['cover_url'] ?? null
            );
        }

        $this->service->saveBook($book);

        $response->getBody()->write('Book added successfully');
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function showBookDetails(Request $request, Response $response, $args): Response
    {
        $bookId = $args['id'];
        $book = $this->service->getBookDetails($bookId);

        $response->getBody()->write($this->twig->render('bookdetails.twig', [
            'book' => $book,
            'ratings' => $this->service->getBookRatings($bookId),
            'reviews' => $this->service->getBookReviews($bookId),
        ]));
        return $response->withHeader('Content-Type', 'text/html');
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

    public function updateBook(Request $request, Response $response, array $args): Response
    {
        $bookId = $args['id'];
        $requestData = $request->getParsedBody();

        $updatedBook = new Book(
            $requestData['title'],
            $requestData['author'],
            $requestData['description'],
            $requestData['pages'],
            $requestData['cover_url']
        );

        $this->service->updateBook($bookId, $updatedBook);

        $response->getBody()->write("Book with ID $bookId updated successfully.");

        return $response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    }

    public function deleteBook(Request $request, Response $response, array $args): Response
    {
        $bookId = $args['id'];

        $this->service->deleteBook($bookId);

        $response->getBody()->write("Book with ID $bookId deleted successfully.");

        return $response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    }
}

