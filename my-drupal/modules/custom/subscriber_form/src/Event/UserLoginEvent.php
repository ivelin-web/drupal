<?php

namespace Drupal\subscriber_form\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\user\UserInterface;

class UserLoginEvent extends Event
{
    const EVENT_NAME = 'subscriber_form_user_login';

    /**
     * @var UserInterface $account
     */
    public $account;

    public function __construct(UserInterface $account)
    {
        $this->account = $account;
    }
}
