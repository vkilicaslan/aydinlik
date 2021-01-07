<?php

namespace Drupal\aydinlik\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_product\Entity\ProductVariation;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Cart Event Subscriber.
 */
class CartEventSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger, CartManagerInterface $cart_manager) {
    $this->messenger = $messenger;
    $this->cartManager = $cart_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CartEvents::CART_ENTITY_ADD => [['addToCart', 100]]
    ];
  }

  /**
   * Alter user field values
   * 
   * @param \Drupal\commerce_cart\Event\CartEntityAddEvent $event
   *   The cart add event
   * 
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function addToCart(CartEntityAddEvent $event) { //bind to checkout
    $dateTime = \DateTime::createFromFormat('Y-m-d',date('Y-m-d'));
    $today = $dateTime->format('Y-m-d');
    $current_user = User::load(\Drupal::currentUser()->id());
    $entityTypeManager = \Drupal::entityTypeManager();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
    $product_variation = $event->getEntity();
    $sku = $product_variation->getSku();
    $from = ["aylik", "yillik", "-"];
    $to = ["Aylık", "Yıllık", " "];
    $name = ucwords(str_replace($from, $to, $sku));
    $epaper_subscription = $entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => 'E-Gazete Aboneliği']);
    $subscription_duration = $entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $name]);
    $current_user->field_abonelik_suresi[0] = ['target_id' => reset($subscription_duration)->id()];
    switch ($sku) {
        case 'aylik-abonelik':
            if (!empty($current_user->field_abonelik_turu)) {
              unset($current_user->field_abonelik_turu);
            }
            $current_user->field_abonelik_baslangic_tarihi->value = $today;
            $current_user->field_abonelik_bitis_tarihi->value = date('Y-m-d', strtotime('+1 month'));
            $current_user->field_abonelik_turu[] = ['target_id' => reset($epaper_subscription)->id()];
            $current_user->save();
            break;
        case '3-aylik-abonelik':
            if (!empty($current_user->field_abonelik_turu)) {
              unset($current_user->field_abonelik_turu);
            }
            $current_user->field_abonelik_baslangic_tarihi->value = $today;
            $current_user->field_abonelik_bitis_tarihi->value = date('Y-m-d', strtotime('+3 months'));
            $current_user->field_abonelik_turu[] = ['target_id' => reset($epaper_subscription)->id()];
            $current_user->save();
            break;
        case '6-aylik-abonelik':
            if (!empty($current_user->field_abonelik_turu)) {
              unset($current_user->field_abonelik_turu);
            }
            $current_user->field_abonelik_baslangic_tarihi->value = $today;
            $current_user->field_abonelik_bitis_tarihi->value = date('Y-m-d', strtotime('+6 months'));
            $current_user->field_abonelik_turu[] = ['target_id' => reset($epaper_subscription)->id()];
            $current_user->save();
            break;
        case 'yillik-abonelik':
            if (!empty($current_user->field_abonelik_turu)) {
              unset($current_user->field_abonelik_turu);
            }
            $current_user->field_abonelik_baslangic_tarihi->value = $today;
            $current_user->field_abonelik_bitis_tarihi->value = date('Y-m-d', strtotime('+1 year'));
            $earchive_subscription = $entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => 'E-Arşiv Aboneliği']);
            $current_user->field_abonelik_turu[] = ['target_id' => reset($earchive_subscription)->id()];
            $current_user->field_abonelik_turu[] = ['target_id' => reset($epaper_subscription)->id()];
            $current_user->save();
        default:
            # code...
            break;
    }
    $current_user->save();
  }
}
