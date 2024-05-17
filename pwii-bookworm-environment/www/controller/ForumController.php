<?php

namespace Bookworm\controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Bookworm\service\ForumService;
use Bookworm\service\TwigRenderer;
use Slim\Psr7\Factory\ResponseFactory;

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

            $htmlContent = $this->twigRenderer->render('forum.twig', ['message' => 'Forum created successfully']);

            $responseFactory = new ResponseFactory();
            $htmlResponse = $responseFactory->createResponse();
            $htmlResponse->getBody()->write($htmlContent);

            return $htmlResponse->withHeader('Content-Type', 'text/html')->withStatus(201);
        } catch (\Exception $e) {
            return $htmlResponse->withHeader('Content-Type', 'text/html')->withStatus(500);
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

    public function updateForum(Request $request, Response $response, array $args): Response
    {
        try {
            $forumId = $args['id'];
            $data = $request->getParsedBody();
            if ($this->forumService->updateForum($forumId, $data)) {
                $updatedForum = $this->forumService->getForumById($forumId);
                $htmlContent = $this->twigRenderer->render('forum.twig', ['forum' => $updatedForum]);

                $responseFactory = new ResponseFactory();
                $htmlResponse = $responseFactory->createResponse();
                $htmlResponse->getBody()->write($htmlContent);

                return $htmlResponse->withHeader('Content-Type', 'text/html');
            } else {
                $responseData = json_encode(['error' => 'Failed to update forum']);
                $response->getBody()->write($responseData);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        } catch (\Exception $e) {
            $responseData = json_encode(['error' => $e->getMessage()]);
            $response->getBody()->write($responseData);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

}
