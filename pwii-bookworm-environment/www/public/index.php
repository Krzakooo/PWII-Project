<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once '../controller/AuthController.php';
require_once '../controller/HomeController.php';
require_once '../controller/ForumController.php';
require_once '../service/AuthService.php';
require_once '../service/TwigRenderer.php';
require_once '../service/ForumService.php';
require_once '../config/dependencies.php';

use Bookworm\controller\AuthController;
use Bookworm\controller\ForumController;
use Bookworm\controller\HomeController;
use Bookworm\service\ForumService;
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

$authController = new AuthController($twigRenderer, $authService);
$homeController = new HomeController($twigRenderer);
$forumController = new ForumController($twigRenderer, $forumService);

$app->get('/', [$homeController, 'index']);

$app->get('/sign-up', [$authController, 'showSignUpForm']);
$app->post('/sign-up', [$authController, 'signUp']);
$app->get('/sign-in', [$authController, 'showSignInForm']);
$app->post('/sign-in', [$authController, 'signIn']);
$app->post('/logout', [$authController, 'logout']);
$app->get('/profile', [$authController, 'showProfile']);
$app->post('/profile', [$authController, 'updateProfile']);

// Routing for Discussion Forum
$app->get('/forums', [$forumController, 'getAllForums']);
$app->get('/api/forums', [$forumController, 'getAllForums']);
$app->post('/api/forums', [$forumController, 'updateForum']);
$app->get('/api/forums/{id}', [$forumController, 'getForumById']);
$app->delete('/api/forums/{id}', [$forumController, 'deleteForum']);

$app->run();
