<?php

namespace Bookworm\controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Bookworm\service\ForumService;
use Bookworm\service\TwigRenderer;

class ForumController
{
    private TwigRenderer $twigRenderer;
    private ForumService $forumService;

    public function __construct(TwigRenderer $twigRenderer, ForumService $forumService)
    {
        $this->twigRenderer = $twigRenderer;
        $this->forumService = $forumService;
    }

    public function showForums(Request $request, Response $response): Response
    {
        $forums = $this->forumService->getAllForums();

        $forumsJson = json_encode($forums);

        $response->getBody()->write($forumsJson);

        return $response->withHeader('Content-Type', 'application/json');
    }


    public function createForum(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $this->forumService->createForum($data);

        return $response->withHeader('Location', '/forums')->withStatus(302);
    }

    public function showForum(Request $request, Response $response, array $args): Response
    {
        $forumId = $args['id'];

        $forum = $this->forumService->getForumById($forumId);

        return $this->twigRenderer->renderResponse($response, 'forums/forum.twig', ['forum' => $forum]);
    }

    public function deleteForum(Request $request, Response $response, array $args): Response
    {
        $forumId = $args['id'];

        $this->forumService->deleteForum($forumId);

        return $response->withHeader('Location', '/forums')->withStatus(302);
    }


}
