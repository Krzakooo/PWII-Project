<?php

namespace Bookworm\service;

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
}
