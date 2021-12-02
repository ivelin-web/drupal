<?php

namespace Drupal\Tests\status_check\Kernel;

use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\status_check\Controller\StatusCheckController
 *
 * @group status_check
 */
class RegistrationTest extends StatusCheckTestBase {

  /**
   * @test
   * @covers ::register
   */
  public function guest_can_register_with_valid_username_and_password() {

    //Arrange
    $url = self::ENDPOINT_API_USER_REGISTER;
    $method = 'POST';
    $content = '{"username":"' . self::VALID_USERNAME . '","password":"' . self::VALID_PASSWORD . '"}';

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->send();

    //Assert
    $this->assertEquals(self::VALID_JWT_TOKEN, $response->getContent());
    $this->assertEquals(202, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::register
   */
  public function guest_cannot_register_with_existing_username() {

    //Arrange
    $this->factory(User::class)->create('john_doe');
    $url = self::ENDPOINT_API_USER_REGISTER;
    $method = 'POST';
    $content = '{"username":"' . self::VALID_USERNAME . '","password":"' . self::VALID_PASSWORD . '"}';

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->send();

    //Assert
    $this->assertEquals('{"message":"User already exist!"}', $response->getContent());
    $this->assertEquals(409, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::register
   */
  public function user_with_JWT_cannot_register() {

    //Arrange
    $this->factory(User::class)->create('john_doe');
    $url = self::ENDPOINT_API_USER_REGISTER;
    $method = 'POST';
    $content = '{"username":"' . self::VALID_USERNAME . '","password":"' . self::VALID_PASSWORD . '"}';
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals('{"message":"Already logged!"}', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::register
   */
  public function guest_cannot_register_without_username() {

    //Arrange
    $url = self::ENDPOINT_API_USER_REGISTER;
    $method = 'POST';
    $content = '{"username":"' . self::EMPTY_USERNAME . '","password":"' . self::VALID_PASSWORD . '"}';

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->send();

    //Assert
    $this->assertEquals('{"message":"Incorrect data!"}', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::register
   */
  public function guest_cannot_register_with_short_username() {

    //Arrange
    $url = self::ENDPOINT_API_USER_REGISTER;
    $method = 'POST';
    $content = '{"username":"' . self::SHORT_USERNAME . '","password":"' . self::VALID_PASSWORD . '"}';

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->send();

    //Assert
    $this->assertEquals('{"message":"Incorrect data!"}', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::register
   */
  public function guest_cannot_register_with_empty_password() {

    //Arrange
    $url = self::ENDPOINT_API_USER_REGISTER;
    $method = 'POST';
    $content = '{"username":"' . self::VALID_USERNAME . '","password":"' . self::EMPTY_PASSWORD . '"}';

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->send();

    //Assert
    $this->assertEquals('{"message":"Incorrect data!"}', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

}
