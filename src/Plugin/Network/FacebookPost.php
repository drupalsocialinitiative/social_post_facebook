<?php

namespace Drupal\social_post_facebook\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\social_api\SocialApiException;
use Drupal\social_post\Plugin\Network\SocialPostNetwork;
use Drupal\social_post_facebook\Settings\FacebookPostSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

use League\OAuth2\Client\Provider\Facebook;

/**
 * Defines Social Post Facebook Network Plugin.
 *
 * @Network(
 *   id = "social_post_facebook",
 *   social_network = "Facebook",
 *   type = "social_post",
 *   handlers = {
 *     "settings": {
 *        "class": "\Drupal\social_post_facebook\Settings\FacebookPostSettings",
 *        "config_id": "social_post_facebook.settings"
 *      }
 *   }
 * )
 */
class FacebookPost extends SocialPostNetwork {

  use LoggerChannelTrait;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * Facebook connection.
   *
   * @var \League\OAuth2\Client\Provider\FacebookOAuth
   */
  protected $connection;

  /**
   * The Post text.
   *
   * @var string
   */
  protected $status;

  /**
   * The logger factory object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */

  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('url_generator'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * FacebookPost constructor.
   *
   * @param \Drupal\Core\Render\MetadataBubblingUrlGenerator $url_generator
   *   Used to generate a absolute url for authentication.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Used for logging errors.
   */
  public function __construct(MetadataBubblingUrlGenerator $url_generator,
                              array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory,
                              LoggerChannelFactory $logger_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->urlGenerator = $url_generator;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Sets the underlying SDK library.
   *
   * @return \League\OAuth2\Client\Provider\Facebook
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\League\OAuth2\Client\Provider\Facebook';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Facebook Library for the league oAuth not found. Class: %s.', $class_name));
    }
    /* @var \Drupal\social_auth_facebook\Settings\FacebookAuthSettings $settings */
    $settings = $this->settings;
    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId'          => $settings->getAppId(),
        'clientSecret'      => $settings->getAppSecret(),
        'redirectUri'       => $GLOBALS['base_url'] . '/user/social-post/facebook/auth/callback',
        'graphApiVersion'   => 'v' . $settings->getGraphVersion(),
      ];

      return new Facebook($league_settings);
    }
    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_post_facebook\Settings\FacebookPostSettings $settings
   *   The Facebook auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(FacebookPostSettings $settings) {
    $app_id = $settings->getAppId();
    $app_secret = $settings->getAppSecret();
    $graph_version = $settings->getGraphVersion();
    if (!$app_id || !$app_secret || !$graph_version) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Define App ID and App Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }

}
