<?php

namespace Drupal\subscriber_form\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

class MyLog implements LoggerInterface
{
    use RfcLoggerTrait;

    /**
     * { @inheritdoc }
     */
    public function log($level, $message, array $context = [])
    {
//        \Drupal::logger('subscriber_form')->notice($message);
    }

}
