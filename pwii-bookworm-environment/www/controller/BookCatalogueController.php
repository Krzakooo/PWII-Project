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

    public function showAddBookForm(Request $request, Response $response): Response
    {

        $books = $this->service->fetchBooks();

        $response->getBody()->write($this->twig->render('catalogue.twig', [

            'books' => $books,
        ]));
        return $response->withHeader('Content-Type', 'text/html');
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

    // Get Book Search for category
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
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');
            return $jsonResponse;
        }

        // If no matching book found, return empty array
        $emptyResponse = new SlimResponse();
        $emptyResponse->getBody()->write(json_encode([]));
        $emptyResponse = $emptyResponse->withHeader('Content-Type', 'application/json');
        return $emptyResponse;
    }
/*
    public function getBookSearchResultsJSON($request, $response, $args)
    {
        $searchString = urlencode($args['id']);

        $url = "https://openlibrary.org/search.json?q={$searchString}&fields=title,author_name,key";

        $json = file_get_contents($url);

        // Decode JSON data
        $data = json_decode($json, true);

        // Check if there are any search results
        if ($data['numFound'] > 0) {
            $searchResults = [];
            // Loop through each document
            foreach ($data['docs'] as $doc) {

                $book = [
                    'title' => $doc['title'],
                    'author_names' => $doc['author_name'],

                ];

                $searchResults[] = $book;
            }

            // Create a new response with JSON data
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

