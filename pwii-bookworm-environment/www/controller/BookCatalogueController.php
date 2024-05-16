<?php

namespace Bookworm\controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Bookworm\service\TwigRenderer;
use Bookworm\model\Book;

class BookCatalogueController
{
    private $twig;
    private Book $book;

    public function __construct(TwigRenderer $twig)
    {
        $this->twig = $twig;
    }

    public function showAddBookForm(Request $request, Response $response): Response
    {
        // Check if the user is authenticated
        $authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

        if (!$authenticated) {
            // If not authenticated, redirect to sign-in page
            return $this->twig->render($response, 'signin.twig');
        }

        $articles = $this->fetchBooks();

        // Render the news page using Twig
        return $this->twig->render($response, 'catalogue.twig', [
            'authenticated' => $authenticated,
            'articles' => $articles, // Pass fetched books to the Twig template
        ]);
    }

    public function addBookToCatalogue(Request $request, Response $response, Book $book): Response
    {
        $authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

        if (!$authenticated) {
            // If not authenticated, redirect to sign-in page
            return $this->twig->render($response, 'signin.twig');
        }
        //modiify return
        // render $this->twig->render($response, 'createBookForm.twig', [

    }

    private function getBookDetails($bookId)
    {
        // Example: Fetch book details from database or API based on $bookId
        // Assuming you have a Book model
        // Example usage of Book model to fetch details from database
        $book = Book::find($bookId); // Assuming this is an Eloquent model

        // Return the fetched book
        return $book;
    }

    public function showBookDetails(Request $request, Response $response, $args)
    {
        // Get book ID from route parameters
        $bookId = $args['id'];

        // Fetch book details from database or API based on the ID
        $book = $this->getBookDetails($bookId);

        // Render Twig template with book details
        return $this->twig->render($response, 'book_details.twig', [
            'book' => $book,
        ]);
    }

    function handleImportForm($isbn)
    {
        // Send GET request to OpenLibrary API
        $url = "https://openlibrary.org/isbn/{$isbn}.json";
        $response = file_get_contents($url);

        // Check if response is valid
        if ($response === false) {
            // Handle error
            return false;
        }

        // Parse JSON response
        $bookData = json_decode($response, true);

        // Extract relevant information
        $title = $bookData['title'];
        $pages = $bookData['number_of_pages'];
        $workIdentifier = $bookData['works'][0]['key'];
        $coverId = $bookData['covers'][0];

        $workUrl = "https://openlibrary.org{$workIdentifier}.json";
        $workResponse = file_get_contents($workUrl);
        $workData = json_decode($workResponse, true);
        $description = $workData['description'];

        $authorIdentifier = $workData['authors'][0]['key'];
        $authorUrl = "https://openlibrary.org{$authorIdentifier}.json";
        $authorResponse = file_get_contents($authorUrl);
        $authorData = json_decode($authorResponse, true);
        $authorName = $authorData['name'];

        $coverUrl = "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg";

        // Create Book object
        $book = new Book($title, $authorName, $description, $pages, $coverUrl);

        // Optionally, validate data and add book to database
        // addBookToDatabase($book);

        return $book;

    }


    private function fetchBooks(): array
    {

        return [
            [
                'title' => 'Article 1',
                'publication_date' => '2024-04-15',
                'author' => 'John Doe',
                'summary' => 'This is a summary of the first article.',
            ],
            [
                'title' => 'Article 2',
                'publication_date' => '2024-04-16',
                'author' => 'Jane Smith',
                'summary' => 'This is a summary of the second article.',
            ],

        ];
    }
}