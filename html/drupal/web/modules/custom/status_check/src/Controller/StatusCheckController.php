<?php

namespace Drupal\status_check\Controller;

use Drupal\user\UserAuth;
use Drupal\user\Entity\User;
use Drupal\status_check\Services\Auth;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\status_check\Services\ProjectsManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StatusCheckController extends ControllerBase {

  private ProjectsManager $projectManager;
  private Auth $auth;
  private UserAuth $userAuth;
  private Request $request;

  /**
   * StatusCheckController constructor.
   *
   * @param \Drupal\status_check\Services\ProjectsManager $project_manager
   * @param \Drupal\status_check\Services\Auth $auth
   * @param \Drupal\user\UserAuth $user_auth
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function __construct(ProjectsManager $project_manager, Auth $auth, UserAuth $user_auth, Request $request) {
    $this->projectManager = $project_manager;
    $this->auth = $auth;
    $this->userAuth = $user_auth;
    $this->request = $request;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\Core\Controller\ControllerBase|static
   */
  public static function create(ContainerInterface $container) {
    $projectManager = $container->get('status_check.projects_manager');
    $auth = $container->get('status_check.auth');
    $userAuth = $container->get('user.auth');
    $request = $container->get('request_stack')->getCurrentRequest();
    return new static($projectManager, $auth, $userAuth, $request);
  }

  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function projects() {
    $project_ids = $this->entityTypeManager()->getStorage('node')->getQuery()->condition('type','project')->execute();
    $projects = $this->projectManager->where($project_ids)->getIdsAndTitlesToArray();
    return new JsonResponse($projects, 200);
  }

  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function projectsWhere() {
    $project_ids = explode(',', $this->request->query->get('project_ids'));
    $projects = $this->projectManager->where($project_ids)->toArray();
    return new JsonResponse($projects, 200);
  }

  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function login() {
    $request_body = json_decode($this->request->getContent(), TRUE);
    $username = $request_body['username'];
    $password = $request_body['password'];

    $uid = $this->userAuth->authenticate($username, $password);
    if ($uid) {
      $jwt = $this->auth->generateJWT($uid);
      return new JsonResponse(['jwt' => $jwt], 202);
    }
    return new JsonResponse(['message' => t('Access denied!')], 403);
  }

  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function register() {
    $request_body = json_decode($this->request->getContent(), TRUE);
    $username = $request_body['username'];
    $password = $request_body['password'];

    if (!isset($username) || mb_strlen(trim($username)) < 3 || empty($password)) {
      return new JsonResponse(['message' => t('Incorrect data!')], 403);
    }

    try {
      $user = User::create();
      $user->setUsername($username);
      $user->setPassword($password);
      $user->enforceIsNew();
      $user->activate();
      $user->save();

      $jwt = $this->auth->generateJWT($user->id());
      return new JsonResponse(['jwt' => $jwt], 202);
    } catch (\Exception $e) {
      return new JsonResponse(['message' => t('User already exist!')], 409);
    }
  }

  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getSettings() {
    $settings = [
      'settings' => NULL,
    ];
    if ($this->auth->getUser()->hasField('field_settings') && !$this->auth->getUser()->get('field_settings')->isEmpty()) {
      $settings = json_decode($this->auth->getUser()->get('field_settings')->getString(), TRUE);
    }

    return new JsonResponse($settings, 200);
  }


  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function setSettings() {
    $settings = $this->request->getContent();
    $this->auth->getUser()->set('field_settings', $settings);
    $this->auth->getUser()->save();

    return new JsonResponse(json_decode($this->auth->getUser()->get('field_settings')->getString(), TRUE), 201);
  }

}
