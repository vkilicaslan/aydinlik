<?php

namespace Drupal\aydinlik\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\current_user;
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
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
    unset($current_user->field_abonelik_suresi);
    unset($current_user->field_abonelik_turu[1]);
    $product_variation = $event->getEntity();
    $sku = $product_variation->getSku();
    $from = ["aylik", "yillik", "-"];
    $to = ["Aylık", "Yıllık", " "];
    $name = ucwords(str_replace($from, $to, $sku));
    $abonelik_suresi = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $name]);
    $edergi_aboneligi = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'E-Gazete Aboneliği']);
    $current_user->field_abonelik_baslangic_tarihi->value = $today;
    $current_user->field_abonelik_suresi[0] = ['target_id' => reset($abonelik_suresi)->id()];
    $current_user->field_abonelik_turu[0]->target_id = reset($edergi_aboneligi)->id();
    switch ($sku) {
        case 'aylik-abonelik':
            unset($current_user->field_abonelik_turu[1]);
            $current_user->field_abonelik_bitis_tarihi->value = date('Y-m-d', strtotime('+1 month'));
            $egazete_aboneligi = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'E-Gazete Aboneliği']);
            $current_user->field_abonelik_turu[] = ['target_id' => reset($egazete_aboneligi)->id()];
            $current_user->save();
            break;
        case '3-aylik-abonelik':
            unset($current_user->field_abonelik_turu[1]);
            $current_user->field_abonelik_bitis_tarihi->value = date('Y-m-d', strtotime('+3 months'));
            $egazete_aboneligi = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'E-Gazete Aboneliği']);
            $current_user->field_abonelik_turu[] = ['target_id' => reset($egazete_aboneligi)->id()];
            $current_user->save();
            break;
        case '6-aylik-abonelik':
            unset($current_user->field_abonelik_turu[1]);
            $current_user->field_abonelik_bitis_tarihi->value = date('Y-m-d', strtotime('+6 months'));
            $egazete_aboneligi = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'E-Gazete Aboneliği']);
            $current_user->field_abonelik_turu[] = ['target_id' => reset($egazete_aboneligi)->id()];
            $current_user->save();
            break;
        case 'yillik-abonelik':
            unset($current_user->field_abonelik_turu);
            $current_user->field_abonelik_bitis_tarihi->value = date('Y-m-d', strtotime('+1 year'));
            $earsiv_aboneligi = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'E-Arşiv Aboneliği']);
            $egazete_aboneligi = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'E-Gazete Aboneliği']);
            $current_user->field_abonelik_turu[] = ['target_id' => reset($earsiv_aboneligi)->id()];
            $current_user->field_abonelik_turu[] = ['target_id' => reset($egazete_aboneligi)->id()];
            $current_user->save();
        default:
            # code...
            break;
    }
    $current_user->save();
  }
}
