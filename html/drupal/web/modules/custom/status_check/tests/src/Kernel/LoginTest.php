<?php

namespace Drupal\Tests\status_check\Kernel;

use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\status_check\Controller\StatusCheckController
 *
 * @group status_check
 */
class LoginTest extends StatusCheckTestBase {

  /**
   * @test
   * @covers ::login
   */
  public function guest_can_get_JWT_with_correct_username_and_password() {

    //Arrange
    $this->factory(User::class)->create('john_doe');
    $url = self::ENDPOINT_API_USER_LOGIN;
    $method = 'POST';
    $content = '{"username":"' . self::VALID_USERNAME . '","password":"' . self::VALID_PASSWORD . '"}';

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->send();

    //Assert
    $this->assertEquals(self::VALID_JWT_RESPONSE, $response->getContent());
    $this->assertEquals(202, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::login
   */
  public function guest_cannot_get_JWT_with_wrong_username() {

    //Arrange
    $this->factory(User::class)->make('john_doe');
    $url = self::ENDPOINT_API_USER_LOGIN;
    $method = 'POST';
    $content = '{"username":"' . self::INVALID_USERNAME . '","password":"' . self::VALID_PASSWORD . '"}';

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->send();

    //Assert
    $this->assertEquals('{"message":"Access denied!"}', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::login
   */
  public function guest_cannot_get_JWT_with_wrong_password() {

    //Arrange
    $this->factory(User::class)->make('john_doe');
    $url = self::ENDPOINT_API_USER_LOGIN;
    $method = 'POST';
    $content = '{"username":"' . self::VALID_USERNAME . '","password":"' . self::INVALID_PASSWORD . '"}';

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->send();

    //Assert
    $this->assertEquals('{"message":"Access denied!"}', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::login
   */
  public function authenticated_user_cannot_get_new_JWT() {

    //Arrange
    $this->factory(User::class)->make('john_doe');
    $url = self::ENDPOINT_API_USER_LOGIN;
    $method = 'POST';
    $content = '{"username":"' . self::VALID_USERNAME . '","password":"' . self::VALID_PASSWORD . '"}';
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals('{"message":"Already logged!"}', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

}
