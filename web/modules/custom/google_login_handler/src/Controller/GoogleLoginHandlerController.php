<?php

namespace Drupal\google_login_handler\Controller;

use Drupal\google_login_handler\JwtTokenHandlerService;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller that handles Google's token to log in
 * and interacts with JwtTokenHandlerService service
 */
class GoogleLoginHandlerController extends ControllerBase {

  /**
   * Variable that will store the service
   *
   * @var \Drupal\google_login_handler\JwtTokenHandlerService
   */
  protected $jwtTokenHandlerService;

  /**
   * Request stack
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(JwtTokenHandlerService $jwtTokenHandlerService, RequestStack $requestStack) {
    $this->jwtTokenHandlerService = $jwtTokenHandlerService;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_login_handler.jwt_token_handler'),
      $container->get('request_stack')
    );
  }

  
  /**
   * Calls the API and returns a user token or message error
   */
  public function validate() {
    $token = $this->requestStack->getCurrentRequest()->get('token');

    if (empty($token)) {
      $response = ['message' => 'no_token_sent'];
      return new JsonResponse($response);
    }

    $validationApiResponse = $this->jwtTokenHandlerService->validate($token);

    if (!empty($validationApiResponse['error'])) {
      return new JsonResponse($validationApiResponse);
    }

    $user = user_load_by_mail($validationApiResponse['email']);

    if (!$user) {
      $response = ['new_user' => true, 'email' => $validationApiResponse["email"]];

      return new JsonResponse($response);
    }

    $jwt = $this->jwtTokenHandlerService->generate($user->uid->value);
    $response = [
      'validation' => [
        'social_auth_token' => $token,
        'user' => $user,
        'validation' => $validationApiResponse,
        'jwt' => $jwt,
      ],
    ];

    return new JsonResponse($response);
  }
}
