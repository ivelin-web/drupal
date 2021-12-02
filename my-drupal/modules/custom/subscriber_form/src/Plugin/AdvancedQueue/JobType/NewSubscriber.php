<?php

namespace Drupal\subscriber_form\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Mail\MailManager;

/**
 * @AdvancedQueueJobType(
 *     id = "new_subscriber",
 *     label = "New Subscriber",
 *     max_retries = 1,
 *     retry_delay = 79200
 * )
 */
class NewSubscriber extends JobTypeBase
{
    public function process(Job $job)
    {
        \Drupal::logger('Job Log')->notice('Job log message');

        $payload = $job->getPayload();

        $params['subject'] = t('New Subscriber');
        $params['first_name'] = $payload['first_name'];
        $params['last_name'] = $payload['last_name'];
        $params['short_description'] = $payload['short_description'];
        $params['from'] = $payload['email'];
        $to = \Drupal::config('system.site')->get('mail');
        $langcode = \Drupal::currentUser()->getPreferredLangcode();

        /**
         * @var MailManager $mailManager
         */
        $mailManager = \Drupal::service('plugin.manager.mail');
        $mailManager->mail('subscriber_form', 'subscriber_mail', $to, $langcode, $params, null, true);

        return JobResult::success('Job was successfully completed');
    }
}
