<?php

namespace Bookworm\Controllers;
require_once '../models/User.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Bookworm\Services\TwigRenderer;

class HomeController
{
    private $twig;

    public function __construct(TwigRenderer $twig)
    {
        $this->twig = $twig;
    }

    public function index(Request $request, Response $response)
    {
        session_start();
        
        $isLoggedIn = isset($_SESSION['user_id']);

        $content = $this->twig->render('home.twig', ['isLoggedIn' => $isLoggedIn]);
        $response->getBody()->write($content);
        return $response;
    }
}
