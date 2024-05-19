<?php

namespace Bookworm\controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
        $authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

        if (!$authenticated) {
            $response->getBody()->write($this->twig->render('signin.twig'));
            return $response->withHeader('Content-Type', 'text/html');
        }

        $books = $this->service->fetchBooks();

        $response->getBody()->write($this->twig->render('catalogue.twig', [
            'authenticated' => $authenticated,
            'books' => $books,
        ]));
        return $response->withHeader('Content-Type', 'text/html');
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
}
