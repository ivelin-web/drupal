<?php

namespace Drupal\Tests\status_check\Kernel;

use Drupal\node\Entity\Node;

/**
 * @coversDefaultClass \Drupal\status_check\Controller\StatusCheckController
 *
 * @group status_check
 */
class ProjectsTest extends StatusCheckTestBase {

  /**
   * @test
   * @covers ::projects
   */
  public function user_can_get_ids_and_titles_for_all_projects() {

    //Arrange
    $this->factory(Node::class, 2)->create('project');
    $url = self::ENDPOINT_API_PROJECTS;
    $method = 'GET';
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals(self::JSON_RESPONSE_PROJECT_IDS_AND_TITLES, $response->getContent());
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::projects
   */
  public function user_still_get_proper_json_response_when_there_are_no_projects() {

    //Arrange
    $url = self::ENDPOINT_API_PROJECTS;
    $method = 'GET';
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals('[]', $response->getContent());
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::projects
   */
  public function user_cannot_get__project_Ids_and_titles_without_JWT() {

    // Arrange
    $url = self::ENDPOINT_API_PROJECTS;
    $method = 'GET';

    //Act
    $response = $this->jsonRequest($url)->using($method)->send();

    //Assert
    $this->assertEquals('{"message":"Authorization fail!"}', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

}
