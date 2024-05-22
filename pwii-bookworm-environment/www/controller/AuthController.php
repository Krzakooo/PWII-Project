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
        session_start();
        if (isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $isLoggedIn = isset($_SESSION['user_id']);

        $content = $this->twig->render('signIn.twig', ['isLoggedIn' => $isLoggedIn]);
        $response->getBody()->write($content);
        return $response;
    }

    public function signIn(Request $request, Response $response): Response
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
            session_start();
            $isLoggedIn = isset($_SESSION['user_id']);
            $content = $this->twig->render('signIn.twig', ['errors' => $errors, 'data' => $data, 'isLoggedIn' => $isLoggedIn]);
            $response->getBody()->write($content);
            return $response;
        }

        if ($this->authService->login($email, $password)) {
            $user = $this->authService->getUserByEmail($email);

            $_SESSION['user_id'] = $user->getId();
            return $response->withHeader('Location', '/')->withStatus(302);
        } else {
            session_start();
            $isLoggedIn = isset($_SESSION['user_id']);
            $content = $this->twig->render('signIn.twig', ['errors' => $errors, 'data' => $data, 'isLoggedIn' => $isLoggedIn]);
            $response->getBody()->write($content);
            return $response;
        }
    }

    public function showSignUpForm(Request $request, Response $response): Response
    {
        session_start();
        if (isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        $isLoggedIn = isset($_SESSION['user_id']);
        $content = $this->twig->render('signIn.twig', ['isLoggedIn' => $isLoggedIn]);
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

        if (!empty($errors)) {
            session_start();
            $isLoggedIn = isset($_SESSION['user_id']);
            $content = $this->twig->render('signup.twig', ['errors' => $errors, 'data' => $data, 'isLoggedIn' => $isLoggedIn]);
            $response->getBody()->write($content);
            return $response;
        }

        // Create new user using AuthService's createUser method
        $user = $this->authService->signUp($email, $password, $username);

        if ($user) {
            session_start();
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['flash_message'] = "You are now signed up, welcome to your site";

            return $response->withHeader('Location', '/profile')->withStatus(302);
        } else {
            $errors[] = "An account with this email address already exists.";
            session_start();
            $isLoggedIn = isset($_SESSION['user_id']);
            $content = $this->twig->render('signup.twig', ['errors' => $errors, 'data' => $data, 'isLoggedIn' => $isLoggedIn]);
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

        $profilePictureUrl = $user->getProfilePicture();
        $isLoggedIn = isset($_SESSION['user_id']);

        $content = $this->twig->render('profile.twig', [
            'currentUser' => $user,
            'profilePictureUrl' => $profilePictureUrl,
            'isLoggedIn' => $isLoggedIn,
            'userId' => $userId
        ]);
        $response->getBody()->write($content);
        return $response;
    }

    public function updateProfile(Request $request, Response $response): Response
    {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/sign-in')->withStatus(302);
        }

        $user = $this->authService->getCurrentUser();
        $data = $request->getParsedBody();
        $email = $data['email'];
        $username = $data['username'];
        $errors = [];
        $responseData = [];

        if (!empty($email) && $email !== $user->getEmail()) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "The email address is not valid.";
            } elseif ($this->authService->getUserByEmail($email)) {
                $errors[] = "The email address is already registered.";
            } else {
                $success = $this->authService->updateEmail($user->getId(), $email);
                if ($success) {
                    $responseData['email'] = $email;
                } else {
                    $errors[] = "Failed to update email. Please try again later.";
                }
            }
        }

        if (!empty($username) && $username !== $user->getUsername()) {
            $existingUser = $this->authService->getUserByUsername($username);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $errors[] = "The username is already taken.";
            } else {
                $success = $this->authService->updateUsername($user->getId(), $username);
                if ($success) {
                    $responseData['username'] = $username;
                } else {
                    $errors[] = "Failed to update username. Please try again later.";
                }
            }
        }

        $uploadedFiles = $request->getUploadedFiles();
        $profilePicture = $uploadedFiles['profile_picture'] ?? null;

        if ($profilePicture && $profilePicture->getError() === UPLOAD_ERR_OK) {
            $uploadPath = __DIR__ . '/../public/uploads';
            $fileName = $this->uploadProfilePicture($profilePicture, $uploadPath);
            if ($fileName) {
                $success = $this->authService->updateProfilePicture($user->getId(), $fileName);
                if ($success) {
                    $responseData['profilePictureUrl'] = '/uploads/' . $fileName;
                } else {
                    $errors[] = "Failed to update profile picture. Please try again later.";
                }
            } else {
                $errors[] = "Failed to upload profile picture. Please try again.";
            }
        }

        if (!empty($errors)) {
            $responseData['errors'] = $errors;
        } else {
            $responseData['success'] = true;
        }

        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    }


    private function uploadProfilePicture(UploadedFileInterface $file, string $uploadPath): ?string
    {
        if ($file->getSize() > 1048576) {
            return null;
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
        $fileExtension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            return null;
        }

        $imageSize = @getimagesize($file->getStream()->getMetadata('uri'));
        if ($imageSize === false) {
            return null;
        }
        list($width, $height) = $imageSize;

        if ($width > 400 || $height > 400) {
            return null;
        }

        $uuid = uniqid();
        $newFilename = "{$uuid}.{$fileExtension}";
        $destinationPath = "$uploadPath/$newFilename";

        try {
            $file->moveTo($destinationPath);
            return $newFilename;
        } catch (Exception $e) {
            return null;
        }
    }


}
