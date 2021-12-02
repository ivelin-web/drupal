<?php

namespace Drupal\subscriber_form\Plugin\QueueWorker;

/**
 * @QueueWorker(
 *     id = "email_processor",
 *     title = "My custom Queue Worker",
 *     cron = {"time" = 60}
 * )
 */
class CronEventProcessor extends EmailEventBase
{

}
