<?php

namespace Drupal\subscriber_form\Plugin\QueueWorker;

use Drupal\Core\Mail\MailManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @inheritdoc
 */
class EmailEventBase extends QueueWorkerBase implements ContainerFactoryPluginInterface
{
    /**
     * @var MailManager
     */
    protected $mail;

    public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManager $mail)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);

        $this->mail = $mail;
    }

    /**
     * { @inheritdoc }
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('plugin.manager.mail')
        );
    }

    /**
     * Process a single item of Queue
     * { @inheritdoc }
     */
    public function processItem($data)
    {
        \Drupal::logger('Queue Logger')->notice('TEST LOG');
        $params['subject'] = t('New Subscriber');
        $params['first_name'] = $data->first_name;
        $params['last_name'] = $data->last_name;
        $params['short_description'] = $data->short_description;
        $params['from'] = $data->email;
        $to = \Drupal::config('system.site')->get('mail');
        $langcode = \Drupal::currentUser()->getPreferredLangcode();

        $this->mail->mail('subscriber_form', 'subscriber_mail', $to, $langcode, $params, null, true);
    }
}
