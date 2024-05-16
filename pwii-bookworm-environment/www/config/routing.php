<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Bookworm\Controller\AuthController;
use Bookworm\Controller\HomeController;
use Bookworm\controller\BookCatalogueController;
use Bookworm\service\AuthService;
use Bookworm\service\TwigRenderer;
use Bookworm\Dependencies;

require __DIR__ . '/../vendor/autoload.php';
require_once '../controller/AuthController.php';
require_once '../controller/HomeController.php';
require_once '../controller/BookCatalogueController.php';
require_once '../services/AuthService.php';
require_once '../services/TwigRenderer.php';
require_once '../config/dependencies.php';

$app = AppFactory::create();

// Middleware
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Dependency injection
$twigRenderer = new TwigRenderer();
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader);
$pdo = Dependencies::connect();
$authService = new AuthService($pdo);

// Controllers
$authController = new AuthController($twigRenderer, $authService);
$homeController = new HomeController($twigRenderer);
$bookCatalogueController = new BookCatalogueController($twigRenderer);

// Routes
$app->get('/', function (Request $request, Response $response, $args) use ($homeController) {
    return $homeController->index($request, $response, $args);
});

$app->get('/sign-up', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->showSignUpForm($request, $response, $args);
});
$app->post('/sign-up', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->signUp($request, $response, $args);
});

$app->get('/sign-in', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->showSignInForm($request, $response, $args);
});
$app->post('/sign-in', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->signIn($request, $response, $args);
});

$app->post('/logout', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->logout($request, $response, $args);
});

$app->get('/profile', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->showProfile($request, $response, $args);
});
$app->post('/profile', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->updateProfile($request, $response, $args);
});

$app->get('/catalogue', function (Request $request, Response $response, $args) use ($authController) {
    return $bookCatalogueController->showAddBookForm($request, $response, $args);
});
$app->post('/catalogue', function (Request $request, Response $response, $args) use ($authController) {
    return $bookCatalogueController->addBookToCatalogue($request, $response, $args);
});

$app->post('/catalogue/{id}', function (Request $request, Response $response, $args) use ($authController) {
    return $bookCatalogueController->updateProfile($request, $response, $args);
});

$app->run();
