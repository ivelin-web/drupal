<?php

namespace Drupal\Tests\status_check\Kernel;

use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\status_check\Controller\StatusCheckController
 *
 * @group status_check
 */
class UserSettingsTest extends StatusCheckTestBase {

  /**
   * @test
   * @covers ::getSettings
   */
  public function user_can_get_his_settings_with_valid_JWT() {

    //Arrange
    $this->factory(User::class)->create('john_doe', [
      'field_settings' => self::SETTINGS,
    ]);
    $url = self::ENDPOINT_API_USER_SETTINGS;
    $method = 'GET';
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals(self::SETTINGS, $response->getContent());
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::getSettings
   */
  public function user_cannot_get_his_settings_without_JWT() {

    //Arrange
    $this->factory(User::class)->create('john_doe', [
      'field_settings' => self::SETTINGS,
    ]);
    $url = self::ENDPOINT_API_USER_SETTINGS;
    $method = 'GET';

    //Act
    $response = $this->jsonRequest($url)->using($method)->send();

    //Assert
    $this->assertEquals('{"message":"Authorization fail!"}', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::getSettings
   */
  public function user_without_settings_still_get_settings_response() {

    //Arrange
    $this->factory(User::class)->create('john_doe');
    $url = self::ENDPOINT_API_USER_SETTINGS;
    $method = 'GET';
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals(self::EMPTY_SETTINGS, $response->getContent());
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * @test
   * @covers ::setSettings
   */
  public function user_can_set_his_settings_with_valid_JWT() {

    //Arrange
    $this->factory(User::class)->create('john_doe');
    $url = self::ENDPOINT_API_USER_SETTINGS;
    $method = 'POST';
    $content = self::SETTINGS;
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals(201, $response->getStatusCode());
    $this->assertEquals(self::SETTINGS, $response->getContent());
  }

  /**
   * @test
   * @covers ::setSettings
   */
  public function user_cannot_set_his_settings_with_invalid_JWT() {

    //Arrange
    $this->factory(User::class)->create('john_doe', [
      'field_settings' => self::SETTINGS,
    ]);
    $url = self::ENDPOINT_API_USER_SETTINGS;
    $method = 'POST';
    $content = self::SETTINGS;
    $cookie = self::INVALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals('{"message":"Authorization fail!"}', $response->getContent());
  }

  /**
   * @test
   * @covers ::setSettings
   */
  public function user_can_update_his_settings_with_valid_JWT() {

    //Arrange
    $this->factory(User::class)->create('john_doe', [
      'field_settings' => self::SETTINGS,
    ]);
    $url = self::ENDPOINT_API_USER_SETTINGS;
    $method = 'POST';
    $content = self::SETTINGS;
    $cookie = self::VALID_COOKIE;

    //Act
    $response = $this->jsonRequest($url)->using($method)->withContent($content)->withCookie($cookie)->send();

    //Assert
    $this->assertEquals(201, $response->getStatusCode());
    $this->assertEquals(self::SETTINGS, $response->getContent());
  }

}
