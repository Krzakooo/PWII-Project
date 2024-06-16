<?php

namespace Bookworm\controller;
require_once '../model/User.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Bookworm\service\TwigRenderer;

class HomeController
{
    private $twig;

    public function __construct(TwigRenderer $twig)
    {
        $this->twig = $twig;
    }

    public function getUserIdFromSession(): ?int
    {
        session_start();
        return $_SESSION['user_id'] ?? null;
    }

    public function index(Request $request, Response $response): Response
    {
        $userId = $this->getUserIdFromSession();

        $isLoggedIn = isset($_SESSION['user_id']);


        $content = $this->twig->render('home.twig', ['isLoggedIn' => $isLoggedIn, 'userId' => $userId]);
        $response->getBody()->write($content);
        return $response;
    }
}
