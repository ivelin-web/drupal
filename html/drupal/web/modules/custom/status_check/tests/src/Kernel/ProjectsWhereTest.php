<?php

namespace Drupal\Tests\status_check\Kernel;

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * @coversDefaultClass \Drupal\status_check\Controller\StatusCheckController
 *
 * @group status_check
 */
class ProjectsWhereTest extends StatusCheckTestBase {

  /**
   * @test
   * @covers ::projectsWhere
   */
  public function user_can_get_json_with_concrete_project_with_environment_and_check_by_providing_project_id() {

    //Arrange
    $this->factory(Node::class)->create('project', [], function($project) {
      $project->get('field_environments')->appendItem($this->factory(Paragraph::class)->create('environment', [], function($environment) {
        $environment->get('field_checks')->appendItem($this->factory(Paragraph::class)->create('check', [] , function($check) {
          $action = $this->factory(Paragraph::class)->create('go_to');
          $assert = $this->factory(Paragraph::class)->create('response_status_code');
          $check->get('field_actions')->appendItem($action);
          $check->get('field_assertions')->appendItem($assert);
          return $check;
        }));
        return $environment;
      }));
      return $project;
    });
    $url = self::ENDPOINT_API_PROJECTS_WHERE . '?project_ids=1';
    $method = 'GET';
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals(self::JSON_RESPONSE_PROJECT_WITH_ONE_ENVIRONMENT_WITH_ONE_CHECK_STATUS_CHECK, $response->getContent());
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::projectsWhere
   */
  public function user_can_get_json_with_two_projects_with_two_environments_with_two_checks_with_two_assertions_and_actions_by_providing_project_ids() {

    //Arrange
    $this->factory(Node::class, 2)->create('project', [], function($project) {
      $environments = $this->factory(Paragraph::class, 2)->create('environment', [], function($environment) {
        $checks = $this->factory(Paragraph::class, 2)->create('check', [] , function($check) {
          $go_to = $this->factory(Paragraph::class)->create('go_to');
          $click = $this->factory(Paragraph::class)->create('click');
          $check->get('field_actions')->appendItem($go_to);
          $check->get('field_actions')->appendItem($click);
          $assertions = $this->factory(Paragraph::class, 2)->create('response_status_code');
          $check->get('field_assertions')->appendItem($assertions[0]);
          $check->get('field_assertions')->appendItem($assertions[1]);
          return $check;
        });
        $environment->get('field_checks')->appendItem($checks[0]);
        $environment->get('field_checks')->appendItem($checks[1]);
        return $environment;
      });
      $project->get('field_environments')->appendItem($environments[0]);
      $project->get('field_environments')->appendItem($environments[1]);
      return $project;
    });
    $url = self::ENDPOINT_API_PROJECTS_WHERE . '?project_ids=1,2';
    $method = 'GET';
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals(self::JSON_RESPONSE_TWO_PROJECTS_WITH_TWO_ENVIRONMENTS_WITH_TWO_CHECKS_WITH_TWO_ASSERTIONS_AND_ACTIONS, $response->getContent());
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::projectsWhere
   */
  public function user_cannot_access_projects_info_without_valid_JWT() {

    //Arrange
    $this->factory(Node::class)->create('project', [], function($project) {
      $project->get('field_environments')->appendItem($this->factory(Paragraph::class)->create('environment', [], function($environment) {
        $environment->get('field_checks')->appendItem($this->factory(Paragraph::class)->create('check', [] , function($check) {
          $action = $this->factory(Paragraph::class)->create('go_to');
          $assert = $this->factory(Paragraph::class)->create('response_status_code');
          $check->get('field_actions')->appendItem($action);
          $check->get('field_assertions')->appendItem($assert);
          return $check;
        }));
        return $environment;
      }));
      return $project;
    });
    $url = self::ENDPOINT_API_PROJECTS_WHERE . '?project_ids=1';
    $method = 'GET';

    //Act
    $response = $this->jsonRequest($url)->using($method)->send();

    //Assert
    $this->assertEquals('{"message":"Authorization fail!"}', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::projectsWhere
   */
  public function user_will_get_empty_correct_json_response_when_he_do_not_have_any_projects() {

    //Arrange
    $url = self::ENDPOINT_API_PROJECTS_WHERE . '?project_ids=1';
    $method = 'GET';
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals('{"data":[]}', $response->getContent());
    $this->assertEquals(200, $response->getStatusCode());
  }

}
