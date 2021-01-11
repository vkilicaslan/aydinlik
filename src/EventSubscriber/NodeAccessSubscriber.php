<?php

namespace Drupal\aydinlik\EventSubscriber;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a request subscriber to deny access to protected nodes.
 */
class NodeAccessSubscriber implements EventSubscriberInterface {

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * NodeAccessSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user account.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => [['denyAccessForRestrictedNodes', 0]]];
  }

  /**
   * Deny access to restricted node content.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Kernel event.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   When user does not have access to the restricted nodes.
   */
  public function denyAccessForRestrictedNodes(GetResponseEvent $event) {
      
    $route_match = RouteMatch::createFromRequest($event->getRequest());
    if (($node = $route_match->getParameter('node')) && $node instanceof NodeInterface) {
        $config = \Drupal::config('aydinlik.settings');
        $current_user = User::load(\Drupal::currentUser()->id());
        $roles = $current_user->getRoles();
        $login = Url::fromRoute('user.login');
        $messenger = \Drupal::messenger();
        if ($node->bundle() == 'e_dergi') {
            if ($current_user->isAnonymous()) {
                $messenger->addStatus($config->get('girisyapmesaji'));
                $redirect = new RedirectResponse($login->toString());
                $event->setResponse($redirect);
            } 
            elseif (!$current_user->hasPermission('bypass permission checks')) {
                $publication_date = $node->field_derginin_ciktigi_ay_yil->value;
                $subscription_start_date = $current_user->field_abonelik_baslangic_tarihi->value;
                $subscription_end_date = $current_user->field_abonelik_bitis_tarihi->value;
                $subscription_duration = Term::load($current_user->field_abonelik_suresi->referencedEntities()[0]->tid->value);
                $epaper_subscription = Term::load($current_user->field_abonelik_turu->referencedEntities()[0]->tid->value);
                if (!str_contains($epaper_subscription->getName(), 'E-Gazete')) {
                    $messenger->addStatus($config->get('satinalmesaji'));
                    $redirect = new RedirectResponse($login->toString());
                    $redirect->send();
                }
                if (str_contains($subscription_duration->getName(), 'Yıllık')) {
                    if ($publication_date>$subscription_end_date) {
                    $messenger->addStatus($config->get('icerikaboneligiaraligimesaji'));
                    $redirect = new RedirectResponse($login->toString());
                    $redirect->send();
                    }
                }
                if (!str_contains($subscription_duration->getName(), 'Yıllık')) {
                    if (!($subscription_start_date<$publication_date && $publication_date<$subscription_end_date) || !($subscription_end_date>$publication_date)) {
                        $messenger->addStatus($config->get('icerikaboneligiaraligimesaji'));
                        $redirect = new RedirectResponse($login->toString());
                        $redirect->send();
                    }
                }
            }
        }
        if ($node->bundle() == 'e_arsiv') {
            if ($current_user->isAnonymous()) {
                $messenger->addMessage($config->get('girisyapmesaji'),TRUE);
                $redirect = new RedirectResponse($login->toString());
                $event->setResponse($redirect);
            } 
            elseif ($current_user->isAuthenticated()) {
                    $earchives_subscription = Term::load($current_user->field_abonelik_turu->referencedEntities()[1]->tid->value);
                    if (!str_contains($earchives_subscription->getName(), 'E-Arşiv')) {
                        $messenger->addStatus($config->get('satinalmesaji'));
                        $redirect = new RedirectResponse('/satin-al');
                        $redirect->send();
                    }
                }
            }
        }
    }

}
