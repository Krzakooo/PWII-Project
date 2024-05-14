<?php

namespace Salle\LSCryptoNews\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class BookCatalogueController
{
    private Twig $twig;

    public function __construct(Twig $twig)
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

    public function addBookToCatalogue(Request $request, Response $response): Response
    {
        $authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

        if (!$authenticated) {
            // If not authenticated, redirect to sign-in page
            return $this->twig->render($response, 'signin.twig');
        }
        //modiify return
        // render $this->twig->render($response, 'createBookForm.twig', [

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