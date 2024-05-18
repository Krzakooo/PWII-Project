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

    public function __construct(TwigRenderer     $twigRenderer,
                                ForumPostService $forumPostService,
                                ForumService     $forumService,
                                AuthService      $authService)
    {
        $this->twigRenderer = $twigRenderer;
        $this->forumPostService = $forumPostService;
        $this->forumService = $forumService;
        $this->authService = $authService;
    }

    public function renderForumPostsPage(Request $request, Response $response, array $args)
    {
        $forumId = (int)$args['forumId'];

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
            if (!$this->isAuthenticated()) {
                throw new \Exception('User not authenticated');
            }

            $userId = $_SESSION['user_id'];
            $forumId = $request->getAttribute('forumId');

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
            ];

            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($responseData));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');

            return $jsonResponse->withStatus(201);
        } catch (\Exception $e) {
            error_log($e->getMessage()); // Log the error message
            $errorResponse = [
                'error' => $e->getMessage(),
            ];
            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($errorResponse));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');
            return $jsonResponse->withStatus(500);
        }
    }


    private function isAuthenticated()
    {
        return isset($_SESSION['user_id']);
    }
}
