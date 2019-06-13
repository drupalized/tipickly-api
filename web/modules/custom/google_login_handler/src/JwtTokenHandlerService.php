<?php

namespace Drupal\google_login_handler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;

/**
 * Class that handles JWT tokens
 */
class JwtTokenHandlerService implements ContainerFactoryPluginInterface {

  /**
   * The JWT token
   * 
   * @var \Drupal\google_login_handler\JwtTokenHandlerService
   */
  protected $jwtToken;

  /**
   * Stores the configuration factory
   * 
   * @var \Drupal\Core\Config\ConfigFactoryInterface;
   */
  protected $configFactory;

  /**
   * Guzzle Http Client
   * 
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory, Client $httpClient) {
    $this->jwtToken = null;
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }


  /**
   * Return a JWT token
   */
  public function validate(string $token) {      

    $config = $this->configFactory->get('google_login_handler.settings');

    try {
      $response = $this->httpClient->request('GET', $config->get('google_login_handler.google_api_url') . '?access_token=' . $token);
      $data = $response->getBody()->getContents();
    } catch (\GuzzleHttp\Exception\ClientException $exception) {
      $data = $exception->getResponse()->getBody()->getContents();
    } catch (\GuzzleHttp\Exception\RequestException $exception) {
      $data = $exception->getResponse()->getBody()->getContents();
    } catch (\GuzzleHttp\Exception\ServerException $exception) {
      $data = $exception->getResponse()->getBody()->getContents();
    }

    $data = $this->clean_response($data);

    return $data;
  }

  /**
   * Return a cleaned response from server
   */
  protected function clean_response(string $string) {

    $string = str_replace('\n', '', $string);
    $string = rtrim($string, ',');
    $string = trim($string) ;
    $json = json_decode($string, true);

    return $json;
  }

  /**
   * Return JWT token
   */
  public function generate(string $user_uid) {

    $config = $this->configFactory->get('google_login_handler.settings');
    
    $header = json_encode(
      [
        'typ' => 'JWT',
        'alg' => 'HS256', 
        'iss' => $config->get('google_login_handler.token_issuer'), 
        'sub' => $user_uid, 
        'exp' => $config->get('google_login_handler.expiration_time'), 
        'iat' => time(),
        'aud' => $config->get('google_login_handler.token_audience'), 
      ]
    );
    
    $payload = json_encode(['uid' => $user_uid]);

    $base64UrlEncodedHeader = $this->base64_url_encode($header);
    $base64UrlEncodedPayload = $this->base64_url_encode($header);

    $signature = hash_hmac('sha256', $base64UrlEncodedHeader . "." . $base64UrlEncodedPayload, $config->get('google_login_handler.signature_key'), true);

    $base64UrlEncodedSignature = $this->base64_url_encode($signature);

    $jwt = $base64UrlEncodedHeader . "." . $base64UrlEncodedPayload . "." . $base64UrlEncodedSignature;

    return $jwt;

  }

  /**
   * Return base64 encoded data
   */
  protected function base64_url_encode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
  }
}
