<?php

namespace Drupal\aydinlik\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Aydinlik module messages
 */
class AydinlikMessagesForm extends ConfigFormBase {

    /**
     * {@inheritDoc}
     */
    public function getFormId() {
        return 'aydinlik.settings';
    }

    /**
     * {@inheritDoc}
     */
    protected function getEditableConfigNames() {
        return ['aydinlik.settings'];
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('aydinlik.settings');
        $form['girisyapmesaji'] = [
            '#type' => 'text_format',
            '#title' => 'Misafir kullanıcılara gösterilecek mesaj',
            '#default_value' => $config->get('girisyapmesaji'),
            '#format' => 'full_html',
        ];
        $form['satinalmesaji'] = [
            '#type' => 'text_format',
            '#title' => 'Kayıtlı kullanıcı olup da e-gazete aboneliği olmayanlara gösterilecek satın alın mesajı',
            '#default_value' => $config->get('satinalmesaji'),
        ];
        $form['icerikaboneligiaraligimesaji'] = [
            '#type' => 'text_format',
            '#title' => 'İçerik aboneliği e-gazetenin çıkış tarihi aralığında olmayanlara gösterilecek mesaj',
            '#default_value' => $config->get('icerikaboneligiaraligimesaji'),
        ];
        $form['earsivabonesidegilmesaji'] = [
            '#type' => 'text_format',
            '#title' => 'E-arşiv aboneliği olmayanlara gösterilecek mesaj',
            '#default_value' => $config->get('earsivabonesidegilmesaji'),
        ];
        $form['egazeteaboneliksuresibittimesaji'] = [
            '#type' => 'text_format',
            '#title' => 'E-gazete abonelik süresi bitenlere gösterilecek mesaj',
            '#default_value' => $config->get('egazeteaboneliksuresibittimesaji'),
        ];
        $form['egazeteaboneliksuresibitecekmesaji'] = [
            '#type' => 'text_format',
            '#title' => 'E-gazete abonelik süresi bitmek üzere olanlara gösterilecek mesaj',
            '#default_value' => $config->get('egazeteaboneliksuresibitecekmesaji'),
        ];
        $form['kaydet'] = [
            '#type' => 'submit',
            '#value' => 'Mesajları kaydet',
        ];
        return $form;
    }

    /**
     * {@inheritDoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->configFactory()->getEditable('aydinlik.settings');
        $config->set('girisyapmesaji', $form_state->getValue('girisyapmesaji'));
        $config->set('satinalmesaji', $form_state->getValue('satinalmesaji'));
        $config->set('icerikaboneligiaraligimesaji', $form_state->getValue('icerikaboneligiaraligimesaji'));
        $config->set('earsivabonesidegilmesaji', $form_state->getValue('earsivabonesidegilmesaji'));
        $config->set('egazeteaboneliksuresibittimesaji', $form_state->getValue('egazeteaboneliksuresibittimesaji'));
        $config->set('egazeteaboneliksuresibitecekmesaji', $form_state->getValue('egazeteaboneliksuresibitecekmesaji'));
        $config->set('aboneliksuresimesaji', $form_state->getValue('aboneliksuresimesaji'));
        $config->save();
        return parent::submitForm($form, $form_state);
    }
}