<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once 'AuthController.php';
require_once 'HomeController.php';
require_once '../services/AuthService.php';
require_once '../services/TwigRenderer.php';
require_once '../config/dependencies.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Bookworm\Controllers\AuthController;
use Bookworm\Controllers\HomeController;
use Bookworm\Services\AuthService;
use Bookworm\Services\TwigRenderer;
use Bookworm\Dependencies;
use Twig\Loader\FilesystemLoader;
use Twig\Environment as Twig;
use Selective\BasePath\BasePathMiddleware;

$app = AppFactory::create();

$app->addRoutingMiddleware();

$app->addErrorMiddleware(true, true, true);

$twigRenderer = new TwigRenderer();

$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Twig($loader);

$pdo = Dependencies::connect();
$authService = new AuthService($pdo);

$authController = new AuthController($twigRenderer, $authService);
$homeController = new HomeController($twigRenderer);

$app->get('/', [$homeController, 'index']);

$app->get('/sign-up', [$authController, 'showSignUpForm']);
$app->post('/sign-up', [$authController, 'signUp']);
$app->get('/sign-in', [$authController, 'showSignInForm']);
$app->post('/sign-in', [$authController, 'signIn']);
$app->post('/logout', [$authController, 'logout']);

$app->run();
