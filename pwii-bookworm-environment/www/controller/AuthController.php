<?php

namespace Bookworm\controller;

require_once '../model/User.php';

use Bookworm\model\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Bookworm\service\TwigRenderer;
use Bookworm\service\AuthService;
use Psr\Http\Message\UploadedFileInterface;
use Exception;

class AuthController
{
    private $twig;
    private $authService;

    public function __construct(TwigRenderer $twig, AuthService $authService)
    {
        $this->twig = $twig;
        $this->authService = $authService;
    }

    public function getUserIdFromSession(): ?int
    {
        session_start();
        return $_SESSION['user_id'] ?? null;
    }

    public function showSignInForm(Request $request, Response $response): Response
    {
        $userId = $this->getUserIdFromSession();
        if (isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $isLoggedIn = isset($_SESSION['user_id']);

        $content = $this->twig->render('signIn.twig', ['isLoggedIn' => $isLoggedIn, 'userId' => $userId]);
        $response->getBody()->write($content);
        return $response;
    }

    public function signIn(Request $request, Response $response): Response
    {
        session_start();

        // Parse JSON body
        $data = json_decode($request->getBody()->getContents(), true);

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $errors = [];

        if (empty($email)) {
            $errors[] = "The email field is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "The email address is not valid.";
        }

        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Add debugging statements here
        error_log("Attempting login for email: $email");

        if ($this->authService->login($email, $password)) {
            $user = $this->authService->getUserByEmail($email);
            if ($user) {
                $_SESSION['user_id'] = $user->getId();
                error_log("Login successful for user ID: " . $user->getId());
                $response->getBody()->write(json_encode(['success' => true]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $errors[] = "Failed to retrieve user details after login.";
                error_log("Failed to retrieve user details for email: $email");
            }
        } else {
            $errors[] = "Invalid email or password.";
            error_log("Login failed for email: $email");
        }

        $response->getBody()->write(json_encode(['success' => false, 'errors' => $errors]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }


    public function showSignUpForm(Request $request, Response $response): Response
    {
        $userId = $this->getUserIdFromSession();
        if (isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        $isLoggedIn = isset($_SESSION['user_id']);
        $content = $this->twig->render('signup.twig', ['isLoggedIn' => $isLoggedIn, 'userId' => $userId]);
        $response->getBody()->write($content);
        return $response;
    }

    public function signUp(Request $request, Response $response): Response
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
        
        $userId = $this->getUserIdFromSession();

        if (!empty($errors)) {
            
            $isLoggedIn = isset($_SESSION['user_id']);
            $content = $this->twig->render('signup.twig', ['errors' => $errors, 'data' => $data, 'isLoggedIn' => $isLoggedIn, 'userId' => $userId]);
            $response->getBody()->write($content);
            return $response;
        }

        $user = $this->authService->signUp($email, $password, $username);

        if ($user) {
            
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['flash_message'] = "You are now signed up, welcome to your site";

            return $response->withHeader('Location', '/profile')->withStatus(302);
        } else {
            $errors[] = "An account with this email address already exists.";
            
            $isLoggedIn = isset($_SESSION['user_id']);
            $content = $this->twig->render('signup.twig', ['errors' => $errors, 'data' => $data, 'isLoggedIn' => $isLoggedIn, 'userId' => $userId]);
            $response->getBody()->write($content);
            return $response;
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        session_start();
        if (isset($_SESSION['user_id'])) {

            $this->authService->logout();

            return $response->withHeader('Location', '/sign-in')->withStatus(302);
        } else {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
    }

    public function getUserById(int $id): ?User
    {
        session_start();
        $user = $this->authService->getUserById($id);

        if ($user && $this->authService->isLoggedIn()) {
            return $user;
        } else {
            return null;
        }
    }

    public function showProfile(Request $request, Response $response): Response
    {
        $userId = $this->getUserIdFromSession();

        if (!$userId) {
            return $response->withHeader('Location', '/sign-in')->withStatus(302);
        }

        $user = $this->authService->getUserById($userId);

        $profilePicture = $user->getProfilePicture();
        $isLoggedIn = isset($_SESSION['user_id']);

        $content = $this->twig->render('profile.twig', [
            'currentUser' => $user,
            'profilePicture' => $profilePicture,
            'isLoggedIn' => $isLoggedIn,
            'userId' => $userId
        ]);
        $response->getBody()->write($content);
        return $response;
    }


    public function updateUser(Request $request, Response $response): Response
    {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/sign-in')->withStatus(302);
        }
    
        $userId = $_SESSION['user_id'];
        $email = $_POST['email'] ?? null;
        $username = $_POST['username'] ?? null;
        $profilePicture = $_FILES['profilePicture'] ?? null;
        $errors = [];
        $responseData = [];
        
        if ($email !== null || $username !== null || $profilePicture !== null) {
            $profilePictureBase64 = null;
    
            if ($profilePicture !== null) {
                if ($profilePicture['error'] === UPLOAD_ERR_OK) {
                    $tempFilePath = $profilePicture['tmp_name'];
                    $fileContents = file_get_contents($tempFilePath);
                    $profilePictureBase64 = base64_encode($fileContents);
                } else {
                    $errors[] = "File upload error.";
                }
            }
    
            if (empty($errors)) {
                $success = $this->authService->updateUserDetails($userId, $email, $username, $profilePictureBase64);
                if ($success) {
                    $responseData['success'] = true;
                } else {
                    $errors[] = "Failed to update user details. Please try again later.";
                }
            }
        } else {
            $errors[] = "No data provided for update.";
        }
    
        if (!empty($errors)) {
            $responseData['errors'] = $errors;
        }
    
        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    }


}
