<?php

/**
 * @file
 * Contains \Drupal\rules\Event\UserLoginEvent.
 */

namespace Drupal\rules\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a user logs in.
 *
 * @see rules_user_login()
 */
class UserLoginEvent extends Event {

  const EVENT_NAME = 'rules_user_login';

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  public $account;

  /**
   * Constructs the object.
   *
   * @param $account
   *   The account of the user logged in.
   */
  public function __construct($account) {
    $this->account = $account;
  }

}
