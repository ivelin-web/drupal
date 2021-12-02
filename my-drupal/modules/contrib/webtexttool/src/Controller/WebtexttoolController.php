<?php

/**
 * @file
 * Contains \Drupal\webtexttool\Controller\WebtexttoolController.
 */

namespace Drupal\webtexttool\Controller;

use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Url;

/**
 * Class WebtexttoolController
 *
 * @package Drupal\webtexttool\Controller
 */
class WebtexttoolController extends ControllerBase {

  /**
   * Base url of the webtexttool API.
   */
  const WEBTEXTTOOL_URL = 'https://api.textmetrics.com';

  /**
   * Helper function to check if user is authenticated.
   *
   * @return bool|mixed|string
   */
  public function webtexttoolIsAuthenticated() {
    return $this->webtexttoolConnect('/user/authenticated');
  }

  /**
   * Implements searchkeyword from api.
   *
   * @param string $keyword
   * @param string $database
   * @param string $language
   * @return bool|mixed|string
   */
  public function webtexttoolSearchKeyword(string $keyword, string $database = 'us', string $language = 'us') {
    return $this->webtexttoolConnect('/keywords/' . UrlHelper::encodePath($keyword). '/'. $database . '/' . $language, $this->webtexttoolGetToken());
  }

  /**
   * Implements GetSuggestions from api.
   *
   * @param string $html
   * @param string $keywords
   * @param string $ruleset
   * @param string $language
   * @param array $synonyms
   * @return array
   */
  public function webtexttoolAnalyseSeo(string $html, string $keywords, string $ruleset, string $language = 'en', array $synonyms = []): array {

    // Set default parameters
    $parameters = array(
      'content' => $html,
      'keywords' => $keywords,
      'languageCode' => $language,
      'ruleset' => $ruleset,
      'synonyms' => $synonyms,
    );

    $response = $this->webtexttoolConnect('/page/suggestions',$this->webtexttoolGetToken(), 'post', $parameters);
    $response = json_decode(json_encode($response),true);

    return $this->makeUniformResponseSeo($response);
  }

  /**
   *
   * Connects to the webtexttool API.
   *
   * @param string $function
   * @param string $token
   * @param string $method
   * @param array $parameters
   *
   * @return bool|mixed|string
   */
  protected function webtexttoolConnect(string $function, string $token = '', string $method = 'get', array $parameters = array()) {

    // If we don't have a token, get one.
    if($token == '') {
      $token = $this->webtexttoolLogin();
    }
    try {
      // Construct headers and client.
      $headers = array('Content-Type' => 'application/json', 'WttSource' => 'Drupal', 'Authorization' => 'bearer ' . $token);
      $client = new Client([
        'base_uri' => WebtexttoolController::WEBTEXTTOOL_URL,
        'headers' => $headers
      ]);

      // Post handling.
      if($method == 'post'){
        $request = $client->post($function, [
          'body' => json_encode($parameters),
        ]);
      }
      // Get handling.
      else{
        $request = $client->get($function);
      }

      // Get the response body of the request.
      $data = (string) $request->getBody();

      // Return if the response is empty.
      if (empty($data)) {
        return FALSE;
      }

      // Return the result of the request.
      else{
        $data = json_decode($data);
        if(!empty($data)) {
          return $data;
        }
        else{
          return false;
        }
      }
    }
    // Error handling.
    catch (RequestException $e) {
      Drupal::messenger()->addMessage(t('Unable to connect to the Textmetrics service please check your credentials and try again later.'), 'error');
      return FALSE;
    }
  }

  /**
   * Logs in to webtexttool api service.
   *
   * @return false|void
   */
  public function webtexttoolLogin() {

    // Create headers and response body.
    $headers = array('Content-Type' => 'application/json', 'WttSource' => 'Drupal');
    $body = array(
      'Language' => Drupal::state()->get('webtexttool.settings.language'),
      'RememberMe' => TRUE,
      'UserName' => Drupal::state()->get('webtexttool.settings.name'),
      'Password' => Drupal::state()->get('webtexttool.settings.pass'),
    );
    try {

      // Construct client and request.
      $client = new Client([
        'base_uri' => WebtexttoolController::WEBTEXTTOOL_URL,
      ]);

      $request = $client->post("/user/login", [
        'headers' => $headers,
        'body' => json_encode($body),
        'timeout' => 60,
      ]);

      // Check if we have a response body.
      $data = (string) $request->getBody();
      if (empty($data)) {
        return FALSE;
      }
      else{

        // Decode the response.
        $data = json_decode($data);

        if ($data) {
          $token = $data->access_token;
          $expiration_date = time() + $data->expires_in;

          // If we received an access token, save it.
          if(!empty($token) && !empty($expiration_date)){
            $this->webtexttoolSetToken($token, $expiration_date);
            return $token;
          }
        }

        // Give feedback about the failed login.
        else {
          Drupal::messenger()->addMessage(t('Unable to login to the Textmetrics service please check your credentials and try again later.'), 'error');
          return false;
        }
      }
    }
    // Error handling.
    catch (RequestException $e) {
      Drupal::messenger()->addMessage(t('Unable to login to the Textmetrics service please check your credentials and try again later.'), 'error');
      return FALSE;
    }
  }

  /**
   * Set token from webtexttool.
   *
   * @param string $token
   * @param int $expiration_date
   */
  public function webtexttoolSetToken(string $token, int $expiration_date) {

    // Set the received token in the temporary storage.
    $tempstore = Drupal::service('tempstore.private')->get('webtexttool');
    $tempstore->set('webtexttooltoken', $token);
    $tempstore->set('webtexttooltoken_expires_on', $expiration_date);
  }

  /**
   * Get token from webtexttool.
   *
   * @param string $token
   * @return false|mixed|string|void
   */
  protected function webtexttoolGetToken(string $token = '') {

    // Jump ship if we already have a token.
    if($token !== ''){
      return $token;
    }

    // Get the token form the temporary storage.
    $tempstore = Drupal::service('tempstore.private')->get('webtexttool');
    $token = $tempstore->get('webtexttooltoken');

    // If we don't have a token in the temporary storage, get a new one.
    if(empty($token)) {
      $token = $this->webtexttoolLogin();
    }

    // Return the token.
    return $token;
  }

  /**
   * Getter for the ruleset options available for the current user.
   *
   * @return array
   *  An array containing the ruleset options.
   */
  public function getRulesetOptions(): array {
    $ruleset_options = array(
      1 => t('Article/blog'),
    );

    // Get the keyword sources from the cache.
    if ($cache = Drupal::cache()->get('webtexttool_getrulesetoptions') ) {
      $ruleset_options = $cache->data;
    }
    // Get the list of languages supported.
    else {
      $rulesets = $this->webtexttoolConnect('/project/GetLightDocumentTypes');

      if ($rulesets) {
        foreach ($rulesets as $ruleset) {
          $ruleset_options[$ruleset->Id] = $ruleset->Name;
        }

        // Set the keyword sources in the cache.
        Drupal::cache()->set('webtexttool_getrulesetoptions', $ruleset_options);
      }
    }

    // Return the keywords.
    return $ruleset_options;
  }

  /**
   * Implements getkeywordsources from api.
   *
   * @return array
   */
  public function webtexttoolGetSources(): array {

    $keywords = array();

    // Get the keyword sources from the cache.
    if ($cache = Drupal::cache()->get('webtexttool_getkeywordsources') ) {
      $keywords = $cache->data;
    }
    // Get the list of languages supported.
    else {
      $keywords_languages = $this->webtexttoolConnect('/keywords/sources');

      if ($keywords_languages) {
        foreach ($keywords_languages as $keyword) {
          $keywords[$keyword->Value] = $keyword->Name;
        }

        // Set the keyword sources in the cache.
        Drupal::cache()->set('webtexttool_getkeywordsources', $keywords);
      }
    }

    // Return the keywords.
    return $keywords;
  }

  /**
   * Page callback for the account status page.
   *
   * @return array
   */
  public function accountStatus(): array {

    // Check if the user is authenticated.
    $is_authenticated = $this->webtexttoolIsAuthenticated();

    // If not, try to login the user.
    if (!$is_authenticated) {
      $this->webtexttoolLogin();
    }

    // Return the markup of the account.
    return array(
      '#markup' => $this->webtexttoolAccount(),
    );
  }

  /**
   * Get an account.
   *
   * @return int|mixed|null
   */
  public function webtexttoolAccount() {

    // Get the current account.
    $account = $this->webtexttoolConnect('/user/info', $this->webtexttoolGetToken());

    if ($account) {

      // Create a render array.
      $render = array(
        '#prefix' => '<div class="webtexttool-account">',
        'account' => array(
          '#markup' => 'Username: ' . $account->UserName . ' FullName: ' . $account->FullName . ' Subscription: ' . $account->SubscriptionName,
        ),
        '#suffix' => '</div>'
      );

      return render($render);
    }

    // Give feedback that the user is not currently logged in.
    $route = Url::fromRoute('system.admin_config_webtexttool.settings');
    $link = Link::fromTextAndUrl($this->t('My account')->render(), $route);

    $render = [
      '#markup' => $this->t('Unable to load your account. Please check and update your credentials at the settings page. '),
      'link' => $link->toRenderable(),
    ];

    return render($render);
  }

  /**
   * Returns a preset of the response of the node. Either an earlier saved response, or a dummy response to build the
   * Form structure of the content quality tab.
   *
   * @param EntityInterface|null $node
   * @return array|mixed
   */
  public function getResponsePreset(EntityInterface $node = NULL) {
    // Check if we have a viable node.
    if ($node->id()) {
      $record = Drupal::database()->select('webtexttool', 'wtt')
        ->condition('nid', $node->id())
        ->fields('wtt', array('content_quality_response'))
        ->orderBy('vid', 'DESC')
        ->range(0,1)
        ->execute();

      $earlier_response = '';
      while ($row = $record->fetchAssoc()) {
        $earlier_response = $row['content_quality_response'];
      }

      // Decode the response.
      $response = json_decode($earlier_response, TRUE);
    }

    // Return the decoded response if available, or a dummy preset if not.
    if (!empty($response)) {
      return $response;
    }
    else {
      return $this->getDummyUniformResponseContentQuality();
    }
  }

  /**
   * Returns a dummy response for the content quality check.
   *
   * @return array
   */
  private function getDummyUniformResponseContentQuality(): array {

    $dummy_return_response = array(
      'original' => array(
        'Suggestions' => array(
          'PageScore' => 0,
          'Suggestions' => array(),
          'PageScoreTag' => '',
          'RuleSet' => 'ContentQuality',
        ),
        'ModifiedDate' => '',
        'Details' => array(
          'ReadingTime' => '00:00',
          'ReadingLevel' => '',
          'ReadingValues' => array(),
        ),
      ),
      'title' => 'Content Quality',
      'suggestions' => array(
        array(
          'Tag' => 'Readability',
          'MetaTag' => 'Readability',
          'Rules' => array(),
          'Importance' => 0,
          'Score' => 0,
          'Penalty' => 0,
          'Tooltip' => NULL,
          'SortIndex' => 0,
        ),
        array(
          'Tag' => 'Adjectives',
          'MetaTag' => 'Adjectives',
          'Rules' => array(),
          'Importance' => 0,
          'Score' => 0,
          'Penalty' => 0,
          'Tooltip' => NULL,
          'SortIndex' => 0,
        ),
        array(
          'Tag' => 'Whitespaces',
          'MetaTag' => 'Whitespaces',
          'Rules' => array(),
          'Importance' => 0,
          'Score' => 0,
          'Penalty' => 0,
          'Tooltip' => NULL,
          'SortIndex' => 0,
        ),
        array(
          'Tag' => 'Gender',
          'MetaTag' => 'Gender',
          'Rules' => array(),
          'Importance' => 0,
          'Score' => 0,
          'Penalty' => 0,
          'Tooltip' => NULL,
          'SortIndex' => 0,
        ),
        array(
          'Tag' => 'Sentiment',
          'MetaTag' => 'Sentiment',
          'Rules' => array(),
          'Importance' => 0,
          'Score' => 0,
          'Penalty' => 0,
          'Tooltip' => NULL,
          'SortIndex' => 0,
        ),
      ),
      'stats' => array(
        'Reading time' => '00:00',
        'Reading level' => t('Not analyzed yet')
      ),
    );

    $account = $this->webtexttoolConnect('/user/info', $this->webtexttoolGetToken());
    if ($account && in_array('AdvancedLanguageLevel', $account->Features) !== FALSE) {

      // Add advanced reading level preset here.
      $dummy_return_response["suggestions"][0]['Metadata'] = array(
        'Settings' => array(
          array(
            'DisplayName' => 'A1',
            'Value' => 1,
          ),
          array(
            'DisplayName' => 'A2',
            'Value' => 12,
          ),
          array(
            'DisplayName' => 'B1',
            'Value' => 2,
          ),
          array(
            'DisplayName' => 'B2',
            'Value' => 14,
          ),
          array(
            'DisplayName' => 'C1',
            'Value' => 3,
          ),
          array(
            'DisplayName' => 'C2',
            'Value' => 16,
          ),
        ),
      );
    }

    return $dummy_return_response;
  }

  /**
   * Post-process the suggestion.
   *
   * @param array $suggestion
   * @return array
   */
  public function postProcessExtraInfoObject(array $suggestion): array {

    if (isset($suggestion['Rules']) && count($suggestion['Rules'])) {
      foreach ($suggestion['Rules'] as $delta => $rule) {

        if (isset($rule['ExtraInfo'])) {
          $extra_info_decoded = json_decode($rule['ExtraInfo'], TRUE);

          // Remove blacklisted words that are Drupal specific.
          if (isset($extra_info_decoded["List"]) && count($extra_info_decoded["List"])) {
            foreach ($extra_info_decoded["List"] as $list_delta => $item) {

              if (in_array($item['word'], $this->getBlacklistedWords())) {
                unset($extra_info_decoded['List'][$list_delta]);
              }

              if (strpos($item['word'], 'pager]Drupal') !== FALSE) {
                unset($extra_info_decoded['List'][$list_delta]);
              }
            }
          }

          $suggestion['Rules'][$delta]['ExtraInfo'] = $extra_info_decoded;
        }
      }
    }

    return $suggestion;
  }

  /**
   * Implements search keyword from api.
   *
   * @param string $html
   * @param array $settings
   * @param string $language
   * @return array|void
   */
  public function analyseContentQuality(string $html, int $rule_set, array $settings = array(), string $language = 'en') {

    $readability = 0;
    if (isset($settings['toggle_Readability']) && $settings['toggle_Readability'] == TRUE && isset($settings['Readability']['readability_setting']['readability_options'])) {
      $readability = $settings['Readability']['readability_setting']['readability_options'];
    }

    $content_quality_parameters = array(
      'QualityLevels' => array(
        // Readability can be 0, 1, 2, 3
        'ReadingLevel' => $readability,
        'DifficultWordsLevel'  => $readability,
        'LongSentencesLevel'  => $readability,

        // toggle_Adjectives triggers two items to call.
        'AdjectivesLevel' => $settings['toggle_Adjectives'] ?? 1,
        'AdjectivesList' => $settings['toggle_Adjectives'] ?? 1,
        'BulletPointsLevel' => $settings['toggle_Bulletpoints'] ?? 1,
        'WhitespacesLevel' => $settings['toggle_Whitespaces'] ?? 1,
      ),
      'content' => $html,
      'languageCode' => $language,
      'ContentLanguageCode' => null,
      'SmartSearch' => FALSE,
      'RuleSet' => ((int) $rule_set > 20) ? $rule_set : 20
    );

    if (isset($settings['toggle_Gender']) && $settings['toggle_Gender'] == 1) {
      // GenderLevel can be 'm', 'f' or 'n'
      $content_quality_parameters['QualityLevels']['GenderLevel'] = $settings['Gender']['gender_setting']['gender_options'] ?? 'n';
    }

    if (isset($settings['toggle_Sentiment']) && $settings['toggle_Sentiment'] == 1) {
      // SentimentLevel can be 'positive', 'neutral' or 'negative'
      $content_quality_parameters['QualityLevels']['SentimentLevel'] = $settings['Sentiment']['sentiment_setting']['sentiment_options'] ?? 'neutral';
    }

    $content_quality_response = $this->webtexttoolConnect('/contentquality/suggestions', $this->webtexttoolGetToken(), 'post', $content_quality_parameters);

    if ($content_quality_response) {
      $response_array = json_decode(json_encode($content_quality_response), TRUE);
      return $this->makeUniformResponseContentQuality($response_array);
    }
  }

  /**
   * Returns a unified seo response.
   *
   * @param array $response
   * @return array
   */
  private function makeUniformResponseSeo(array $response): array {
    return array(
      'original' => $response,
      'title' => 'SEO',
      'suggestions' => $response["Suggestions"],
      'stats' => array(
        $this->t('Page score')->render() => round($response['PageScore']),
        $this->t('Page score potential')->render() => $response['PageScorePotential'],
        $this->t('Encouragement')->render() => $response['PageScoreTag'],
        $this->t('Keyword count')->render() => $response['KeywordCount'],
        $this->t('Word count')->render() => $response['WordCount'],
      )
    );
  }

  /**
   * Returns a unified content quality response.
   *
   * @param array $response
   * @return array
   */
  private function makeUniformResponseContentQuality (array $response): array {
    $return_response = array(
      'original' => $response,
      'title' => 'Content Quality',
      'suggestions' => $response["Suggestions"]["Suggestions"],
      'stats' => array(
        'Reading time' => $response["Details"]["ReadingTime"],
        'Reading level' => $response["Details"]["ReadingLevel"],
      ),
      'page_score' => $response['Suggestions']['PageScore'],
      'page_score_tag' => $response['Suggestions']['PageScoreTag'],
    );

    if (isset($response["Details"]["Sentiment"])) {
      $return_response['stats']['Sentiment'] = $response["Details"]["Sentiment"];
    }

    return $return_response;
  }

  /**
   * Returns a unified content quality settings structure so we can set in on the node.
   *
   * @param FormStateInterface $form_state
   * @return array
   */
  public function getUnifiedContentQualitySettings(FormStateInterface $form_state): array {

    $values = $form_state->getValues();

    $unified_content_quality = [];
    $response = json_decode($values['response'], TRUE);

    if (isset($response["suggestions"])) {
      foreach ($response["suggestions"] as $suggestion) {

        $suggestion_name = $suggestion['Tag'];
        $suggestion_name_lower = strtolower($suggestion['Tag']);
        $unified_content_quality['toggle_' . $suggestion_name] = $values['toggle_' . $suggestion_name] ?? 1;

        if (isset($suggestion["Metadata"]["Settings"]) && $suggestion["Metadata"]["Settings"]) {
          $unified_content_quality[$suggestion_name] = [
            $suggestion_name_lower . '_setting' => [
             $suggestion_name_lower . '_options' => $values[$suggestion_name_lower . '_options']
            ]
          ];
        }
      }
    }

    return $unified_content_quality;
  }

  /**
   * Encodes and saves the content quality response of the node.
   *
   * @param EntityInterface $node
   * @param array $response
   * @throws Exception
   */
  public function saveContentQualityResponse(EntityInterface $node, array $response) {

    // Check if we have a response and the CQC settings on the node are set.
    if (!empty($response) && !empty($node->webtexttool_content_quality_settings) && $node->id()) {
      if (isset($response['original'])) {
        unset($response['original']);
      }

      $response_encoded = json_encode($response);

      // First check if there is already an db entry for this node.
      if ($node->id()) {
        $result = Drupal::database()->select('webtexttool', 'wtt')
          ->condition('wtt.nid', $node->id())
          ->condition('wtt.vid', $node->getLoadedRevisionId())
          ->fields('wtt', ['nid'])
          ->execute()
          ->fetchAll();

        if (empty($result)) {
          // Insert a row in the webtexttool table for this node.
          Drupal::database()->insert('webtexttool')
            ->fields(array(
              'nid' => $node->id(),
              'vid' => $node->getLoadedRevisionId(),
              'content_quality_response' => $response_encoded
            ))
            ->execute();
        }
        else {
          // Update the row and inject the encoded response into the webtextool table.
          Drupal::database()->merge('webtexttool')
            ->key([
              'nid' => $node->id(),
              'vid'=> $node->getLoadedRevisionId()
            ])
            ->fields(
              [
                'content_quality_response' => $response_encoded
              ]
            )
            ->execute();
        }
      }
    }
  }

  /**
   * Save the content quality settings on the node.
   *
   * @param EntityInterface $node
   * @param array $response
   * @throws Exception
   */
  public function saveContentQualitySettings(EntityInterface $node, array $response) {

    // Check if we have a response and the CQC settings on the node are set.
    if (!empty($response) && !empty($node->webtexttool_content_quality_settings) && $node->id()) {
      $content_quality_settings = json_encode($node->webtexttool_content_quality_settings);

      // First check if there is already an db entry for this node.
      if ($node->id()) {
        $result = Drupal::database()->select('webtexttool', 'wtt')
          ->condition('wtt.nid', $node->id())
          ->condition('wtt.vid', $node->getLoadedRevisionId())
          ->fields('wtt', ['nid'])
          ->execute()
          ->fetchAll();

        if (empty($result)) {
          // Insert a row in the webtexttool table for this node.
          Drupal::database()->insert('webtexttool')
            ->fields(array(
              'nid' => $node->id(),
              'vid' => $node->getLoadedRevisionId(),
              'content_quality_settings' => $content_quality_settings
            ))
            ->execute();
        }
        else {
          // Update the row and inject the encoded response into the webtextool table.
          Drupal::database()->merge('webtexttool')
            ->key([
              'nid' => $node->id(),
              'vid'=> $node->getLoadedRevisionId()
            ])
            ->fields(
              [
                'content_quality_settings' => $content_quality_settings
              ]
            )
            ->execute();
        }
      }
    }
  }

  /**
   * Saves the initial settings.
   *
   * @param EntityInterface $node
   * @param array $settings
   * @throws Exception
   */
  public function saveWebtexttoolSettingsOfNode(EntityInterface $node, array $settings) {
    $result = Drupal::database()->select('webtexttool', 'wtt')
      ->condition('vid', $node->getLoadedRevisionId())
      ->fields('wtt', ['nid', 'keywords', 'lang', 'content_quality_settings', 'ruleset', 'synonyms'])
      ->execute()
      ->fetchAll();

    // Only save the settings in the db, if the node is already saved and has an identifier.
    if ($node->id()) {

      $fields = array(
        'nid' => $node->id(),
        'vid' => $node->getLoadedRevisionId(),
        'keywords' => $settings['webtexttool_keywords'] ?? '',
        'lang' => $settings['webtexttool_language'] ?? '',
        'content_quality_settings' => isset($settings['webtexttool_content_quality_settings']) ? json_encode($settings['webtexttool_content_quality_settings']) : '',
        'ruleset' => $settings['webtexttool_ruleset'] ?? 1,
        'synonyms' => $settings['webtexttool_synonyms'] ?? '',
      );

      if (empty($result)) {
        Drupal::database()->insert('webtexttool')
          ->fields($fields)
          ->execute();
      }
      else {
        Drupal::database()->merge('webtexttool')
          ->key([
            'nid' => $node->id(),
            'vid'=> $node->getLoadedRevisionId()
          ])
          ->fields($fields)
          ->execute();
      }
    }
  }

  /**
   * Returns the synonym word collection that can be used in the API call.
   *
   * @param string $synonyms_value
   * @return array
   */
  public function seoGetSynonymsStructureForApiCall(string $synonyms_value): array {

    $synonyms = [];
    $synonyms_exploded = preg_split("/[\n\r]/", $synonyms_value);
    $synonyms_exploded_cleaned = array_filter($synonyms_exploded);

    $counter = 0;
    foreach ($synonyms_exploded_cleaned as $synonym) {
      if ($counter < 20) {
        $synonyms[$counter] = array('text' => $synonym);
        $counter++;
      }
    }

    return $synonyms;
  }

  /**
   * Provides markup for the given node, respecting metatags and path, with head and body tags.
   *
   * @param EntityInterface $node
   * @param FormStateInterface $form_state
   * @return string
   * @throws Drupal\Core\Entity\EntityMalformedException
   */
  public function constructCompleteMarkupOfPageWithNodeMarkup(EntityInterface $node, FormStateInterface $form_state): string {

    $node_build = Drupal::entityTypeManager()->getViewBuilder('node')->view($node);
    $node_markup = Drupal::service('renderer')->render($node_build);

    // First, get the field name of the metatags field (the name can be anything)
    $values = $form_state->getValues();
    $metatag_field_name = '';

    foreach ($values as $field_key => $field_value) {
      if (strpos($field_key, 'field_') !== FALSE) {
        if (isset($field_value[0]['basic']['title'])) {
          $metatag_field_name = $field_key;
        }
      }
    }

    // Render all metatags by hand.
    $head = '';
    if (isset($node->{$metatag_field_name}) && $values[$metatag_field_name][0]['basic']) {
      $metatags = $values[$metatag_field_name][0]['basic'];

      $token_service = Drupal::token();
      foreach ($metatags as $meta_key => $meta_value) {

        $token_decoded_meta_value = $token_service->replace($meta_value, ['node' => $node]);

        if ($meta_key == 'title') {
          $head .= '<title>' . strip_tags($token_decoded_meta_value) . '</title>';
        }
        else {
          $head .= '<meta name="' . HTML::escape($meta_key) . '" content="' . htmlspecialchars(strip_tags($token_decoded_meta_value)) . '" /> ';
        }
      }
    }
    else {
      $title = $node->getTitle();
      $head .= '<title>' . strip_tags($title) . '</title>';

      // Let the user know there is no metatag field available, while the metatag module is enabled (and a dependency).
      Drupal::messenger()->addMessage('To analyze the page description, add a metatag field to the current content type.', 'warning');
    }

    // Render the custom <meta name="url" content="">.
    $path = '';
    global $base_url;

    if (Drupal::moduleHandler()->moduleExists('path') && $node->id()) {
      $path = $node->toUrl()->toString();
      $alias_clean = str_replace(array('-', '/'), ' ', $path);
      $path = $base_url . '/' . $alias_clean;
    }

    if ($path != '') {
      $head .= '<meta name="url" content="' . $path . '" /> ';
    }

    // Get a very plain version of the html.
    $page = '<html><head>' . $head;
    $page .= '</head><body>' . $node_markup . '</body></html>';

    return $page;
  }

  /**
   * Returns blacklisted words.
   *
   * @return string[]
   */
  private function getBlacklistedWords(): array {
    return array(
      'current',
      'pager]Drupal'
    );
  }

  /**
   * Returns string used in the WordPress plugin
   *
   * @param string $key
   * @return mixed|void
   */
  public function webtexttoolHeading(string $key) {
    $texts = array(
      "CQGenericError" => t("We’re sorry, we could not analyze your content. Please try again or contact support@textmetrics.com in case the issues persist."),
      "ContentRequiredError" => t("Your page needs some content."),
      "ContentMinLengthError" => t("Your page content must have at least 150 words."),
      "GenericError" => t("Something went wrong!"),
      "LanguageNotSupportedError" => t("Detected language is not yet supported!"),
      "PageTitleLabel" => t("Page Title"),
      "PageDescriptionLabel" => t("Page Description"),
      "HeadingsLabel" => t("Headings"),
      "MainContentLabel" => t("Main Content"),
      "MiscellaneousLabel" => t("Miscellaneous"),
      "HeadingsSuggestion" => t("To optimize your content both in terms of readability and SEO, you should structure your content by adding several headings. At the start of your page you normally have an H1 / heading 1 with the title of your page. In some CMS&#39;s / themes this H1 is added automatically. If this is the case, you can select the option &quot;Process page title as H1&quot; from the settings above. Next to H1, you should add smaller heading (H2-H6) to structure your content even further."),
      "MainContentSuggestion" => t("Here you will find several important suggestions for your content. Please have a look at our knowledgebase (Learn tab in the app) to find more background information about these suggestions."),
      "MiscellaneousSuggestion" => t("Here you will find suggestions to optimize your content. These will have smaller impact on overall optimization, but are good to consider and see if they can fit in your content."),
      "PageTitleSuggestion" => t("<p>The Page title is important to search engines. And therefore it&#39;s important to you. Think of a catchy title that will trigger a user to click on your page when it&#39;s listed in the search results. Of course it should also cover the content of the page.</p>\r\n\t"),
      "PageDescriptionSuggestion" => t("<p>The page description is important because it&#39;s shown in the search results and it will tell the search and the users what your page is about. So provide a good description of your content and make sure you follow the suggestion for creating a perfect description of your page.</p>\r\n\t"),
      "Heading1Suggestion" => t("<p>A H1 / Header section at the beginning of your page is important because it&#39;s the readable introduction of your page. In some CMS&#39;s the Page Title is automatically inserted at the top of a page in H1/Header 1.</p>\r\n\t"),
      "Heading2to6Suggestion" => t("<p>Use smaller headings (h2, h3, h4, h5 and/or h6) in your content to highlight / summarize paragraphs. Using headers will make it easier for you reader to &quot;scan&quot; the contents of your page. It allows you to catch the reader&#39;s attention.</p>\r\n\t"),
      "BodySuggestion" => t("<p>These suggestions are related to overall content on your page. Our rules suggest a minimum number of words for your page. Also related to the length of your content, is the number of times you should use your keywords. This way you can avoid to put your keyword too many times in the content (&quot;keyword stuffing&quot;), but also make sure that you use your keyword enough times so it will be clear for the search engine what the content is about.</p>\r\n\t"),
      "ReadabilityLabel" => t("Readability"),
      "AdjectivesLabel" => t("Text credibility"),
      "GenderLabel" => t("Target audience"),
      "WhitespacesLabel" => t("Text layout"),
      "BulletpointsLabel" => t("Bulletpoints"),
      "ReadabilitySuggestion" => t("Readability => multiple checks on complexity level of the content (reading score/long sentences/difficult words)."),
      "AdjectivesSuggestion" => t("Checks the use of adjectives in your text. Over- or underuse of adjectives will decrease effectiveness of your text."),
      "GenderSuggestion" => t("Gender check on level (confidence) of content target."),
      "WhitespacesSuggestion" => t("Checks the use of white spaces in your content. Use this to make your content easier to scan and read."),
      "BulletpointsSuggestion" => t("Checks the use of bulletpoints in your content. Use these to make the text easier to scan and read.")
    );

    if (isset($texts[$key])) {
      return $texts[$key];
    }
  }

  /**
   * Helper function to add a class based on the Score.
   *
   * @param string $score
   * @return string
   */
  public static function scoreToClass(string $score): string {
    // -1 - N/A, 0 - very low, 1 - low, 2 - moderate, 3 - high, 4- very high.
    $class_array = array(
      '-1' => 'yellow',
      '0' => 'red',
      '1' => 'orange',
      '2' => 'yellow',
      '3' => 'light-green',
      '4' => 'green',
    );
    return $class_array[$score];
  }

  /**
   * Helper function to create the markup for and color indicator.
   *
   * @param string $color_class
   * @return string
   */
  public static function colorIndicator(string $color_class): string {
    return "<span class='indicator $color_class'>&nbsp;</span>";
  }

  /**
   * Helper function to add a class based on the OverallScore.
   *
   * @param string $score
   * @return string
   */
  public static function overallScoreToClass(string $score): string {
    // 0 - poor, 1 - moderate, 2 - good.
    $class_array = array(
      '-1' => 'yellow',
      '0' => 'red',
      '1' => 'yellow',
      '2' => 'green',
    );
    return $class_array[$score];
  }

  /**
   * Helper function to change Competition Score to text.
   *
   * @param string $score
   * @return mixed
   */
  public static function volumeScoreToText(string $score) {
    // VolumeScore: -1 - N/A, 0 - very low, 1 - low, 2 - moderate, 3 - high, 4- very high.
    $score_array = array(
      '-1' => t('moderate'),
      '0' => t('very low'),
      '1' => t('low'),
      '2' => t('moderate'),
      '3' => t('high'),
      '4' => t('very high'),
    );
    return $score_array[$score];
  }

  /**
   * Helper function to change Competition Score to text.
   *
   * @param string $score
   * @return string
   */
  public static function competitionScoreToText(string $score): string {
    // -1 - N/A, 0 - very hard, 1- hard, 2 - moderate, 3 - easy, 4 - very easy.
    $score_array = array(
      '-1' => 'moderate',
      '0' => 'very hard',
      '1' => 'hard',
      '2' => 'moderate',
      '3' => 'easy',
      '4' => 'very easy',
    );
    return $score_array[$score];
  }

  /**
   * Helper function to change overall score to text.
   *
   * @param string $score
   * @return string
   */
  public static function overallToText(string $score): string {
    // 0 - poor, 1 = moderate, 2 - good.
    $score_array = array(
      '-1' => 'moderate',
      '0' => 'poor',
      '1' => 'moderate',
      '2' => 'good',
    );
    
    return $score_array[$score];
  }
}