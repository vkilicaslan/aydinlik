<?php

namespace Drupal\aydinlik\EventSubscriber;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a request subscriber to deny access to protected nodes.
 */
class NodeAccessSubscriber implements EventSubscriberInterface {
    use StringTranslationTrait;
    use MessengerTrait;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $current_user;

  /**
   * NodeAccessSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user account.
   */
  public function __construct(AccountInterface $current_user) {
    $this->current_user = $current_user;
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
    $this->current_user = User::load(\Drupal::currentUser()->id());
    $config = \Drupal::config('aydinlik.settings');
    $roles = $this->current_user->getRoles();
    $login = Url::fromRoute('user.login');
    $this->messenger = \Drupal::messenger();
    $route_match = RouteMatch::createFromRequest($event->getRequest());
    if (($node = $route_match->getParameter('node')) && $node instanceof NodeInterface) {
        if (!\Drupal::service('path.matcher')->isFrontPage()) { 
            if ($this->current_user->isAnonymous()) {
                $this->messenger->addMessage(Markup::create($config->get('girisyapmesaji')));
                $redirect = new RedirectResponse($login->toString());
                $event->setResponse($redirect);
            } 
            elseif (!$this->current_user->hasPermission('bypass permission checks')) {
                if ($this->current_user->hasRole('abone')) {
                    if ($node->bundle() == 'e_dergi') {
                        $publication_date = $node->field_derginin_ciktigi_ay_yil->value;
                        $subscription_start_date = $this->current_user->field_abonelik_baslangic_tarihi->value;
                        $subscription_end_date = $this->current_user->field_abonelik_bitis_tarihi->value;
                        $subscription_duration = Term::load($this->current_user->field_abonelik_suresi->referencedEntities()[0]->tid->value);
                        $subscription_type = $this->current_user->get('field_abonelik_turu');
                        $subscription_type_count = $subscription_type->count();
                        if ($subscription_type_count < 2){
                            $epaper_subscription = Term::load($this->current_user->field_abonelik_turu->referencedEntities()[0]->tid->value);
                                if (!str_contains($epaper_subscription->getName(), 'E-Gazete')) {
                                    $this->messenger->addWarning(Markup::create($config->get('satinalmesaji')));
                                    $redirect = new RedirectResponse($login->toString());
                                    $redirect->send();
                                }
                            }                       
                        if (str_contains($subscription_duration->getName(), 'Yıllık')) {
                            if ($publication_date>$subscription_end_date) {
                                $this->messenger->addWarning(Markup::create($config->get('icerikaboneligiaraligimesaji')));
                                $redirect = new RedirectResponse($login->toString());
                            $redirect->send();
                            }
                        }
                        if (!str_contains($subscription_duration->getName(), 'Yıllık')) {
                            if (!($subscription_start_date<$publication_date && $publication_date<$subscription_end_date) || !($subscription_end_date>$publication_date)) {
                                $this->messenger->addWarning(Markup::create($config->get('icerikaboneligiaraligimesaji')));
                                $redirect = new RedirectResponse($login->toString());
                                $redirect->send();
                            }
                        }
                    }
                }
                else {
                    $this->messenger->addWarning(Markup::create($config->get('abonelikaktifdegilmesaji')));
                    $redirect = new RedirectResponse($login->toString());
                    $redirect->send();
                }
                    if ($node->bundle() == 'e_arsiv') {
                        $subscription_type = $this->current_user->get('field_abonelik_turu');
                        $subscription_type_count = $subscription_type->count();
                        if ($subscription_type_count < 2){
                            $earchives_subscription = Term::load($this->current_user->field_abonelik_turu->referencedEntities()[0]->tid->value);
                            if (!str_contains($earchives_subscription->getName(), 'E-Arşiv')) {
                                $this->messenger->addWarning(Markup::create($config->get('earsivabonesidegilmesaji')));
                                $redirect = new RedirectResponse('/satin-al');
                                $redirect->send();
                            }
                        }
                    }
                }
            }
        }
    }
    
}
