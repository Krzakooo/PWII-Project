<?php
declare(strict_types=1);

use Bookworm\controller\BookRatingReviewController;
use Bookworm\controller\ForumController;
use Bookworm\controller\ForumPostController;
use Bookworm\service\BookRatingReviewService;
use Bookworm\service\ForumPostService;
use Bookworm\service\BookCatalogueService;
use Bookworm\service\ForumService;
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
require_once '../controller/BookRatingReviewController.php';
require_once '../service/AuthService.php';
require_once '../service/TwigRenderer.php';
require_once '../service/BookCatalogueService.php';
require_once '../service/BookRatingReviewService.php';
require_once '../config/dependencies.php';
require_once '../service/ForumService.php';
require_once '../service/ForumPostService.php';

$app = AppFactory::create();

// Middleware
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();


// Dependency injection
$twigRenderer = new TwigRenderer();
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader);
$pdo = Dependencies::connect();

//Services
$authService = new AuthService($pdo);
$forumService = new ForumService($pdo);
$forumPostService = new ForumPostService($pdo);
$bookCatalogueService = new BookCatalogueService($pdo);
$bookRatingReviewService= new BookRatingReviewService($pdo);

// Controllers
$authController = new AuthController($twigRenderer, $authService);
$homeController = new HomeController($twigRenderer);
$forumController = new ForumController($twigRenderer, $forumService, $authController, $authService);
$bookCatalogueController = new BookCatalogueController($twigRenderer, $bookCatalogueService, $authController, $authService);
$forumPostController = new ForumPostController($twigRenderer, $forumPostService, $forumService, $authService);
$bookRatingReviewController = new BookRatingReviewController($twigRenderer, $bookRatingReviewService, $authController);

// Routes
$app->get('/', function (Request $request, Response $response, $args) use ($homeController) {
    return $homeController->index($request, $response);
});

$app->get('/get-user/{id}', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->getUserById($args['id']);
});

$app->get('/sign-up', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->showSignUpForm($request, $response);
});
$app->post('/sign-up', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->signUp($request, $response);
});

$app->get('/sign-in', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->showSignInForm($request, $response);
});
$app->post('/sign-in', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->signIn($request, $response);
});

$app->post('/logout', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->logout($request, $response);
});

$app->get('/profile', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->showProfile($request, $response);
});
$app->post('/profile', function (Request $request, Response $response, $args) use ($authController) {
    return $authController->updateUser($request, $response);
});

// Routing to Discuss Forum
$app->get('forums', function (Request $request, Response $response) use ($forumController) {
    return $forumController->getAllForums($request, $response);
});

$app->get('api/forums', function (Request $request, Response $response) use ($forumController) {
    return $forumController->getAllForums($request, $response);
});

$app->post('api/forums', function (Request $request, Response $response) use ($forumController) {
    return $forumController->createForum($request, $response);
});

$app->get('api/forums/{id}', function (Request $request, Response $response, $args) use ($forumController) {
    return $forumController->getForumById($request, $response, $args);
});

$app->delete('api/forums/{id}', function (Request $request, Response $response, $args) use ($forumController) {
    return $forumController->deleteForum($request, $response, $args);
});

// Routing for Forum Posts
$app->get('/forums/{id}/posts', function (Request $request, Response $response, array $args) use ($forumPostController) {
    return $forumPostController->renderForumPostsPage($request, $response, $args);
});

$app->get('/api/forums/{id}/posts', function (Request $request, Response $response, $args) use ($forumPostController) {
    return $forumPostController->renderForumPostsPage($request, $response, $args);
});

$app->post('/api/forums/{id}/posts', function (Request $request, Response $response, $args) use ($forumPostController) {
    return $forumPostController->createForumPost($request, $response, $args);
});

//Book Catalogue
$app->get('/catalogue', function (Request $request, Response $response, $args) use ($bookCatalogueController) {
    return $bookCatalogueController->showAddBookForm($request, $response);
});

$app->post('/catalogue', function (Request $request, Response $response, $args) use ($bookCatalogueController) {
    return $bookCatalogueController->addBookToCatalogue($request, $response);
});

$app->get('/catalogue/{id}', function (Request $request, Response $response, $args) use ($bookCatalogueController) {
    return $bookCatalogueController->getBookDetails($request, $response, $args);
});

// Review & Rating
$app->get('/catalogue/{id}/ratings', function (Request $request, Response $response, $args) use ($bookRatingReviewController) {
    return $bookRatingReviewController->getRatings($request, $response, $args);
});

$app->get('/catalogue/{id}/reviews', function (Request $request, Response $response, $args) use ($bookRatingReviewController) {
    return $bookRatingReviewController->getReviews($request, $response, $args);
});

$app->put('/catalogue/{id}/review', function (Request $request, Response $response, $args) use ($bookRatingReviewController) {
    return $bookRatingReviewController->reviewBook($request, $response, $args);
});

$app->delete('/catalogue/{id}/review', function (Request $request, Response $response, $args) use ($bookRatingReviewController) {
    return $bookRatingReviewController->deleteReview($request, $response, $args);
});

$app->put('/catalogue/{id}/rate', function (Request $request, Response $response, $args) use ($bookRatingReviewController) {
    return $bookRatingReviewController->rateBook($request, $response, $args);
});

$app->delete('/catalogue/{id}/rate', function (Request $request, Response $response, $args) use ($bookRatingReviewController) {
    return $bookRatingReviewController->deleteRating($request, $response, $args);
});


$app->run();
