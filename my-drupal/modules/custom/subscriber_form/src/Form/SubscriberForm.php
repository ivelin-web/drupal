<?php

namespace Drupal\subscriber_form\Form;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\node\Entity\Node;
use Drupal\subscriber_form\Logger\MyLog;
use Drupal\user\Entity\User;

/**
 * Custom subscriber form
 */
class SubscriberForm extends FormBase
{
    public function getFormId()
    {
        return 'subscriber_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#required' => true
        ];

        $form['short_description'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Short Description'),
            '#required' => true
        ];

        $form['first_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('First Name'),
            '#required' => true
        ];

        $form['last_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Last Name'),
            '#required' => true
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Subscribe'),
            '#required' => true
        ];

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Create node
        $subscriber_first_name = $form_state->getValue('first_name');
        $userName = $subscriber_first_name ? $subscriber_first_name : 'Anonymous';
        $newNode = Node::create([
            'type' => 'subscriber',
            'title' => "New Subscriber - $userName",
            'field_subsriber_email' => $form_state->getValue('email'),
            'field_short_description' => $form_state->getValue('short_description'),
            'field_first_name' => $form_state->getValue('first_name'),
            'field_last_name' => $form_state->getValue('last_name')
        ]);
        $newNode->save();

        // Log message
//        /** @var MyLog $log_service */
//        $log_service = \Drupal::service('logger.subscriber_form');
//        $log_service->log(RfcLogLevel::NOTICE, $this->t('New subscriber log'));
        \Drupal::logger('subscriber_form')->notice('New subscriber log');
        \Drupal::messenger()->addMessage($this->t('You have been subscribed successfully!'));

        // Get queue
        /**
         * @var QueueFactory $queue_factory
         */
        $queue_factory = \Drupal::service('queue');

        /**
         * @var QueueInterface $queue
         */
        $queue = $queue_factory->get('email_processor');

        // Create item and add to queue
        $item = new \stdClass();
        $item->email = $form_state->getValue('email');
        $item->short_description = $form_state->getValue('short_description');
        $item->first_name = $form_state->getValue('first_name');
        $item->last_name = $form_state->getValue('last_name');

        $job = Job::create('new_subscriber', (array)$item);
        $q = Queue::load('default');
        $q->enqueueJob($job);
//        $queue->createItem($item);
    }
}
