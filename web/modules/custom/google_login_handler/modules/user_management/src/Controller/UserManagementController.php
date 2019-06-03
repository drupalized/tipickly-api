<?php

namespace Drupal\user_management\Controller;

use Drupal\google_login_handler\JwtTokenHandlerService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * UserManagementController class to handle the CRUD process of users
 */
class UserManagementController extends ControllerBase {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(JwtTokenHandlerService $jwtTokenHandlerService, RequestStack $requestStack, EntityTypeManager $entityTypeManager) {
    $this->jwtTokenHandlerService = $jwtTokenHandlerService;
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_login_handler.jwt_token_handler'),
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }


  /**
   * Method to add users into Drupal.
   */
  public function add() {
    $imageUrl = $this->requestStack->getCurrentRequest()->get('profile');

    if (!empty($imageUrl)) {
      $profileImage = file_get_contents($imageUrl);
      $publicDirectory = 'public://images/';

      file_prepare_directory($publicDirectory, FILE_CREATE_DIRECTORY);

      $image = file_save_data($profileImage, $publicDirectory . basename($imageUrl), FILE_EXISTS_REPLACE);
    }

    $userData = [
      "name" => $this->requestStack->getCurrentRequest()->get('name'),
      "email" => $this->requestStack->getCurrentRequest()->get('email'),
      "password" => $this->requestStack->getCurrentRequest()->get('password'),
      "status" => 1,
      "roles" => [],
      "user_picture" => !empty($imageUrl) ? ['target_id' => $image->id()] : '',
    ];

    $user = $this->entityTypeManager()->getStorage('user')->create($userData);
    $user->save();
    $uid = $user->id();
    $token = $this->jwtTokenHandlerService->generate_token($uid);

    return new JsonResponse($token, 201);
  }

  /**
   * Method to update Drupal users.
   */
  public function update() {
    // @TODO: This getCurrentRequest() is being called multiple times. It's worth
    // having a specific method to load this. If you have to maintain / update this
    // code in the future, you'll have to update it multiple times. If you have
    // a simgle method to load it, you'll update it just once ;).
    $email = $this->requestStack->getCurrentRequest()->get('email');
    $user = load_user_by_mail($email);

    if (empty($email)) {
      $response = [
        'error' => 'no_email_sent',
        'error_description' => 'No e-mail sent',
      ];

      return new JsonResponse($response, 500);
    }

    if (!$user) {
      $response = [
        'error' => 'user_not_found',
        'error_description' => 'User not found',
      ];

      return new JsonResponse($response, 404);
    }

    return new JsonResponse($user, 200);
  }

  /**
   * Method to load Drupal users.
   */
  public function load() {
    $email = $this->requestStack->getCurrentRequest()->get('email');

    if (empty($email)) {
      $response = [
        'error' => 'no_email_sent',
        'error_description' => 'No e-mail sent',
      ];

      return new JsonResponse($response, 500);
    }

    $user = load_user_by_mail($email);

    if (!$user) {
      $response = [
        'error' => 'user_not_found',
        'error_description' => 'User not found',
      ];

      return new JsonResponse($response, 500);
    }

    return new JsonResponse($user, 200);
  }

  public function delete() {
    $email = $this->requestStack->getCurrentRequest()->get('email');
    $user = load_user_by_mail($email);

    if (empty($email)) {
      $response = [
        'error' => 'no_email_sent',
        'error_description' => 'No e-mail sent',
      ];

      return new JsonResponse($response, 500);
    }

    if (!$user) {
      $response = [
        'error' => 'user_not_found',
        'error_description' => 'User not found',
      ];

      return new JsonResponse($response, 404);
    }

    $user = $user->block();

    return new JsonResponse($user, 200);
  }
}
