<?php

namespace Bookworm\service;

use Slim\Psr7\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigRenderer
{
    private $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../public/templates');
        $this->twig = new Environment($loader);
    }

    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }

    public function renderResponse(Response $response, string $template, array $data = []): Response
    {
        $content = $this->twig->render($template, $data);

        $response->getBody()->write($content);

        return $response;
    }

}
