<?php

namespace Bookworm\controller;

use Bookworm\model\Forum;
use Bookworm\model\Post;
use Bookworm\service\AuthService;
use Bookworm\service\ForumPostService;
use Bookworm\service\ForumService;
use Bookworm\service\TwigRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response as SlimResponse;

class ForumPostController
{
    private TwigRenderer $twigRenderer;
    private ForumPostService $forumPostService;
    private ForumService $forumService;
    private AuthService $authService;

    public function __construct(
        TwigRenderer $twigRenderer,
        ForumPostService $forumPostService,
        ForumService $forumService,
        AuthService $authService
    ) {
        $this->twigRenderer = $twigRenderer;
        $this->forumPostService = $forumPostService;
        $this->forumService = $forumService;
        $this->authService = $authService;
    }

    public function renderForumPostsPage(Request $request, Response $response, array $args)
    {
        $authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
        $usernameDefined = isset($_SESSION['username']) && !empty($_SESSION['username']);

        if (!$authenticated) {
            if ($request->getUri()->getPath() !== '/api/forums') {
                return $response->withHeader('Location', '/sign-in')->withStatus(302);
            } else {
                $responseData = json_encode(['error' => 'Unauthorized']);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401)->withBody($responseData);
            }
        } else if (!$usernameDefined && strpos($request->getUri()->getPath(), '/api/') === 0) {
            $responseData = json_encode(['error' => 'Forbidden']);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403)->withBody($responseData);
        }

        $forumId = (int) $args['forumId'];

        $forumData = $this->forumService->getForumById($forumId);
        $forumPosts = $this->forumPostService->getForumPostsByForumId($forumId);

        $data = [
            'forum' => [
                'forumName' => $forumData['title'],
                'forumDescription' => $forumData['description'],
                'forumId' => $forumId,
            ],
            'posts' => $forumPosts,
        ];

        $renderedTemplate = $this->twigRenderer->render('forum_post.twig', $data);

        $response->getBody()->write($renderedTemplate);

        return $response;
    }



    public function createForumPost(Request $request, Response $response): Response
    {
        try {
            session_start();
            if (!$this->authService->isLoggedIn()) {
                throw new \Exception('User not authenticated');
            }

            $userId = $_SESSION['user_id'];
            $forumId = (int) $request->getAttribute('forumId');

            $userId = $_SESSION['user_id'];
            $data = $request->getParsedBody();
            $content = $data['content'] ?? '';

            if (empty($content)) {
                throw new \Exception('Content is required');
            }

            $postData = [
                'forum_id' => $forumId,
                'user_id' => $userId,
                'content' => $content,
            ];

            $postId = $this->forumPostService->createForumPost($postData);

            $responseData = [
                'message' => 'Post created successfully',
                'post_id' => $postId,
                'user_id' => $userId,
            ];

            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($responseData));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');

            return $jsonResponse->withStatus(201);
        } catch (\Exception $e) {
            return $response->withStatus(500);
        }
    }


}
