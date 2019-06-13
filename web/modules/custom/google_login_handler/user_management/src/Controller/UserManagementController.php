<?php

namespace Drupal\user_management\Controller;

use Drupal\google_login_handler\JwtTokenHandlerService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

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
   * Serializer interface
   * 
   * @var \Symfony\Component\Serializer\SerializerInterface
   */

  /**
   * {@inheritdoc}
   */
  public function __construct(JwtTokenHandlerService $jwtTokenHandlerService, RequestStack $requestStack, EntityTypeManagerInterface $entityTypeManager, SerializerInterface $serializer) {
    $this->jwtTokenHandlerService = $jwtTokenHandlerService;
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_login_handler.jwt_token_handler'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('serializer')
    );
  }


  /**
   * Method to add users into Drupal.
   */
  public function add() {
    $imageUrl = $this->getFromRequestStack('profile');

    if (!empty($imageUrl)) {
      $profileImage = file_get_contents($imageUrl);
      $publicDirectory = 'public://images/';

      file_prepare_directory($publicDirectory, FILE_CREATE_DIRECTORY);

      $image = file_save_data($profileImage, $publicDirectory . basename($imageUrl), FILE_EXISTS_REPLACE);
    }

    $userData = [
      "name" => $this->getFromRequestStack('name'),
      "email" => $this->getFromRequestStack('email'),
      "password" => $this->getFromRequestStack('password'),
      "status" => 1,
      "roles" => [],
      "user_picture" => !empty($imageUrl) ? ['target_id' => $image->id()] : '',
    ];

    $user = $this->entityTypeManager()->getStorage('user')->create($userData);
    $user->save();
    $uid = $user->id();
    $token = $this->jwtTokenHandlerService->generate($uid);

    return new JsonResponse($token, 201);
  }

  /**
   * Method to update Drupal users.
   */
  public function update() {
    $email = $this->getFromRequestStack('email');
    $user = user_load_by_mail($email);

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

    return new JsonResponse($this->serializer->serialize($user, 'array'), 200);
  }

  /**
   * Method to load Drupal users.
   */
  public function load() {
    $email = $this->getFromRequestStack('email');

    if (empty($email)) {
      $response = [
        'error' => 'no_email_sent',
        'error_description' => 'No e-mail sent',
      ];

      return new JsonResponse($response, 500);
    }

    $user = user_load_by_mail($email);

    if (!$user) {
      $response = [
        'error' => 'user_not_found',
        'error_description' => 'User not found',
      ];

      return new JsonResponse($response, 500);
    }

    return new JsonResponse($this->serializer->serialize($user, 'json'), 200);
  }

  public function delete() {
    $email = $this->getFromRequestStack('email');
    $user = user_load_by_mail($email);

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

  protected function getFromRequestStack(string $param) {
    return $this->requestStack->getCurrentRequest()->get($param);
  }
}
