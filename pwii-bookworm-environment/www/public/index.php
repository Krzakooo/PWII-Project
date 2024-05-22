<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once '../controller/AuthController.php';
require_once '../controller/HomeController.php';
require_once '../controller/ForumController.php';
require_once '../controller/ForumPostController.php';
require_once '../controller/BookCatalogueController.php';
require_once '../controller/BookRatingReviewController.php';
require_once '../service/AuthService.php';
require_once '../service/TwigRenderer.php';
require_once '../service/ForumService.php';
require_once '../service/BookCatalogueService.php';
require_once '../service/BookRatingReviewService.php';
require_once '../service/ForumPostService.php';
require_once '../config/dependencies.php';

use Bookworm\controller\AuthController;
use Bookworm\controller\ForumController;
use Bookworm\controller\ForumPostController;
use Bookworm\controller\BookCatalogueController;
use Bookworm\controller\BookRatingReviewController;
use Bookworm\controller\HomeController;
use Bookworm\service\ForumPostService;
use Bookworm\service\BookCatalogueService;
use Bookworm\service\ForumService;
use Bookworm\service\BookRatingReviewService;
use Slim\Factory\AppFactory;
use Bookworm\service\AuthService;
use Bookworm\service\TwigRenderer;
use Bookworm\dependencies;
use Twig\Loader\FilesystemLoader;
use Twig\Environment as Twig;

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$twigRenderer = new TwigRenderer();
$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Twig($loader);

$pdo = dependencies::connect();
$authService = new AuthService($pdo);
$forumService = new ForumService($pdo);
$forumPostService = new ForumPostService($pdo);
$bookCatalogueService = new BookCatalogueService($pdo);
$bookRatingReviewService = new BookRatingReviewService($pdo);

$authController = new AuthController($twigRenderer, $authService);
$homeController = new HomeController($twigRenderer);
$forumController = new ForumController($twigRenderer, $forumService);
$forumPostController = new ForumPostController($twigRenderer, $forumPostService, $forumService, $authService);
$bookCatalogueController = new BookCatalogueController($twigRenderer, $bookCatalogueService, $authController);
$bookRatingReviewController = new BookRatingReviewController($twigRenderer, $bookRatingReviewService);


$app->get('/', [$homeController, 'index']);

$app->get('/get-user/{id}', [$authController, 'getUserById']);
$app->get('/sign-up', [$authController, 'showSignUpForm']);
$app->post('/sign-up', [$authController, 'signUp']);
$app->get('/sign-in', [$authController, 'showSignInForm']);
$app->post('/sign-in', [$authController, 'signIn']);
$app->post('/logout', [$authController, 'logout']);
$app->get('/profile', [$authController, 'showProfile']);
$app->post('/profile', [$authController, 'updateProfile']);

// Index for Discussion Forum
$app->get('/forums', [$forumController, 'getAllForums']);
$app->get('/api/forums', [$forumController, 'getAllForums']);
$app->post('/api/forums', [$forumController, 'createForum']);
$app->get('/api/forums/{id}', [$forumController, 'getForumById']);
$app->delete('/api/forums/{id}', [$forumController, 'deleteForum']);

// Index for Forum Posts
$app->get('/forums/{forumId}/posts', [$forumPostController, 'renderForumPostsPage']);
$app->get('/api/forums/{forumId}/posts', [$forumPostController, 'renderForumPostsPage']);
$app->post('/api/forums/{forumId}/posts', [$forumPostController, 'createForumPost']);

// Book catalogue
$app->get('/catalogue', [$bookCatalogueController, 'showAddBookForm']);
$app->post('/catalogue', [$bookCatalogueController , 'addBookToCatalogue']);
$app->get('/catalogue/{id}', [$bookCatalogueController, 'getBookDetails']);

// Rating & Review
$app->get('/catalogue/{id}/ratings', [$bookRatingReviewController, 'getRatings']);
$app->post('/catalogue/{id}/rate', [$bookRatingReviewController, 'createRating']);
$app->get('/catalogue/{id}/reviews', [$bookRatingReviewController, 'getReviews']);
$app->post('/catalogue/{id}/review', [$bookRatingReviewController, 'createReview']);
$app->put('/catalogue/{id}/rate', [$bookRatingReviewController, 'rateBook']);
$app->delete('/catalogue/{id}/rate', [$bookRatingReviewController, 'deleteRating']);
$app->put('/catalogue/{id}/review', [$bookRatingReviewController, 'reviewBook']);
$app->delete('/catalogue/{id}/review', [$bookRatingReviewController, 'deleteReview']);

$app->run();
