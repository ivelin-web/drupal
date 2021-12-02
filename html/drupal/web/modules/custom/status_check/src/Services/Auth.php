<?php

namespace Drupal\status_check\Services;

use Firebase\JWT\JWT;
use Drupal\user\Entity\User;
use Drupal\Core\Access\AccessResult;

class Auth {

  private $user = NULL;

  /**
   * @return null
   */
  public function getUser() {
    if (!$this->user) {
      $token = $this->validateJWT();
      $this->user = User::load($token->uid);
    }
    return $this->user;
  }

  /**
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultInterface
   */
  public function checkAccess() {
    try {
      $token = $this->validateJWT();
      $this->user = User::load($token->uid);
      return AccessResult::allowed();
    } catch (\Throwable $e) {
      return AccessResult::forbidden()->setReason(t('Authorization fail!'));
    }
  }

  /**
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultInterface
   */
  public function guestOnly() {
    try {
      $this->validateJWT();
      return AccessResult::forbidden()->setReason(t('Already logged!'));
    } catch (\Throwable $e) {
      return AccessResult::allowed();
    }
  }

  /**
   * @param $uid
   *
   * @return string
   */
  public function generateJWT($uid) {
    $key = 'salt';
    $token = [
      'uid' => $uid
    ];
    return JWT::encode($token, $key, 'HS256');
  }

  /**
   * @return object
   */
  public function validateJWT() {
    $jwt = \Drupal::request()->cookies->get('jwt');
    if (!$jwt) {
      $jwt = \Drupal::request()->query->get('jwt');
    }
    return JWT::decode(
      $jwt,
      'salt',
      ['HS256']
    );
  }

}
