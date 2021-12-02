<?php

namespace Drupal\subscriber_form\EventSubscriber;

use Drupal\subscriber_form\Event\UserLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserLoginSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            UserLoginEvent::EVENT_NAME => 'onUserLogin',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function onUserLogin(UserLoginEvent $event)
    {
        $loggedUsername = $event->account->getAccountName();
        $randomNumber = rand();

        $textToDisplay = $randomNumber % 2 === 0 ? "Hi, $loggedUsername." : "Hello, $loggedUsername.";

        \Drupal::messenger()->addStatus(t($textToDisplay));
        \Drupal::logger('subscriber_form')->notice(t("User with name $loggedUsername logged in."));
    }
}
