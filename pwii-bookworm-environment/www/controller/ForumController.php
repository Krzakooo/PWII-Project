<?php

namespace Bookworm\controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Bookworm\service\ForumService;
use Bookworm\service\TwigRenderer;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Response as SlimResponse;

class ForumController
{
    private TwigRenderer $twigRenderer;
    private ForumService $forumService;

    public function __construct(TwigRenderer $twigRenderer, ForumService $forumService)
    {
        $this->twigRenderer = $twigRenderer;
        $this->forumService = $forumService;
    }

    public function getAllForums(Request $request, Response $response): Response
    {
        try {
            $forums = $this->forumService->getAllForums();
            return $this->twigRenderer->renderResponse($response, 'forum.twig', ['forums' => $forums]);
        } catch (\Exception $e) {
            $responseData = json_encode(['error' => $e->getMessage()]);
            $response->getBody()->write($responseData);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function createForum(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $this->forumService->createForum($data);

            $responseData = [
                'message' => 'Forum created successfully'
            ];

            $jsonResponse = new SlimResponse();
            $jsonResponse->getBody()->write(json_encode($responseData));
            $jsonResponse = $jsonResponse->withHeader('Content-Type', 'application/json');

            return $jsonResponse->withStatus(201);
        } catch (\Exception $e) {
            return $jsonResponse->withStatus(500);
        }
    }

    public function deleteForum(Request $request, Response $response, array $args): Response
    {
        try {
            $forumId = $args['id'];
            $this->forumService->deleteForum($forumId);
            return $response->withStatus(204);
        } catch (\Exception $e) {
            $responseData = json_encode(['error' => $e->getMessage()]);
            $response->getBody()->write($responseData);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getForumById(Request $request, Response $response, array $args): Response
    {
        try {
            $forumId = $args['id'];
            $forum = $this->forumService->getForumById($forumId);
            if ($forum) {
                $responseData = json_encode($forum);
                $response->getBody()->write($responseData);
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $responseData = json_encode(['error' => 'Forum not found']);
                $response->getBody()->write($responseData);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        } catch (\Exception $e) {
            $responseData = json_encode(['error' => $e->getMessage()]);
            $response->getBody()->write($responseData);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

}
