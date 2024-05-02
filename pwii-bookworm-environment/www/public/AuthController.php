<?php

namespace Bookworm\Controllers;
require_once '../models/User.php';
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Bookworm\Services\TwigRenderer;
use Bookworm\Services\AuthService;





class AuthController
{
    private $twig;
    private $authService;

    public function __construct(TwigRenderer $twig, AuthService $authService)
    {
        $this->twig = $twig;
        $this->authService = $authService;
    }

    public function showSignInForm(Request $request, Response $response)
    {
        session_start();
        if (isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        
        $content = $this->twig->render('signin.twig', []);
        $response->getBody()->write($content);
        return $response;
    }

    public function signIn(Request $request, Response $response)
        {
            $data = $request->getParsedBody();
            $email = $data['email'];
            $password = $data['password'];

            $errors = [];

            if (empty($email)) {
                $errors[] = "The email field is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "The email address is not valid.";
            }

            if (!empty($errors)) {
                $content = $this->twig->render('signin.twig', ['errors' => $errors, 'data' => $data]);
                $response->getBody()->write($content);
                return $response;
            }

            if ($this->authService->login($email, $password)) {
                $user = $this->authService->getUserByEmail($email);
                session_start();
                $_SESSION['user_id'] = $user->getId();
                return $response->withHeader('Location', '/')->withStatus(302);

            } else {
                $errors[] = "The email address or password is incorrect.";
                $content = $this->twig->render('signin.twig', ['errors' => $errors, 'data' => $data]);
                $response->getBody()->write($content);
                return $response;
            }
        }



    public function showSignUpForm(Request $request, Response $response)
    {
        session_start();
        if (isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        
        $content = $this->twig->render('signup.twig', []);
        $response->getBody()->write($content);
        return $response;
    }

    public function signUp(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $email = $data['email'];
        $password = $data['password'];
        $username = $data['username'];
        $repeatPassword = $data['repeatPassword'];

        $errors = [];

        if (empty($email)) {
            $errors[] = "The email field is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "The email address is not valid.";
        } elseif ($this->authService->getUserByEmail($email)) {
            $errors[] = "The email address is already registered.";
        }

        if (empty($password)) {
            $errors[] = "The password field is required.";
        } elseif (strlen($password) < 6 || !preg_match('/[0-9]/', $password)) {
            $errors[] = "The password must contain at least 6 characters and at least one number.";
        }

        if ($password !== $repeatPassword) {
            $errors[] = "The passwords do not match.";
        }

        if (!empty($errors)) {
            $content = $this->twig->render('signup.twig', ['errors' => $errors, 'data' => $data]);
            $response->getBody()->write($content);
            return $response;
        }

        $user = $this->authService->signUp($email, $password, $username);

        if ($user) {
            session_start();
            $_SESSION['user_id'] = $user->getId();
            return $response->withHeader('Location', '/')->withStatus(302);
        } else {
            $errors[] = "An account with this email address already exists.";
            $content = $this->twig->render('signup.twig', ['errors' => $errors, 'data' => $data]);
            $response->getBody()->write($content);
            return $response;
        }
    }

    public function logout(Request $request, Response $response)
    {
        session_start();
        if (isset($_SESSION['user_id'])) {
            
            $this->authService->logout();

            return $response->withHeader('Location', '/sign-in')->withStatus(302);
        } else {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
    }

}
