<?php

namespace Bookworm\controller;

use Bookworm\model\Forum;
use Bookworm\model\Post;
use Bookworm\service\ForumPostService;
use Bookworm\service\ForumService;
use Bookworm\service\TwigRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ForumPostController
{
    private TwigRenderer $twigRenderer;
    private ForumPostService $forumPostService;
    private ForumService $forumService;

    public function __construct(TwigRenderer $twigRenderer,
                                ForumPostService $forumPostService,
                                ForumService $forumService)
    {
        $this->twigRenderer = $twigRenderer;
        $this->forumPostService = $forumPostService;
        $this->forumService = $forumService;
    }

    public function getForumPostsByForumId(Request $request, Response $response, array $args)
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
            'content' => $forumPosts
        ];

        $response->getBody()->write($this->twigRenderer->render('forum_post.twig', $data));

        return $response;
    }

    public function createForumPost(Request $request, Response $response, array $args)
    {
        session_start();
        $forumId = (int)$args['id'];
        $postData = $request->getParsedBody();

        if (!$this->isAuthenticated()) {
            return $response->withHeader('Location', '/sign-in')->withStatus(302);
        }

        $userId = $_SESSION['user_id'];
        $this->forumPostService->createForumPost($forumId, $userId, $postData);

        return $response->withHeader('Location', '/forums/' . $forumId . '/posts')->withStatus(302);
    }

    private function isAuthenticated()
    {
        return isset($_SESSION['user_id']);
    }
}

