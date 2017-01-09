<?php

/**
 * Class ControllerPaymentGingerIdeal
 */
class ControllerPaymentGingerIdeal extends Controller
{
    /**
     * Prefix for fields in admin settings page
     */
    const POST_FIELD_PREFIX = 'ginger_';

    /**
     * @var array
     */
    static $update_fields = [
        'api_key',
        'status',
        'sort_order',
        'order_status_id_new',
        'order_status_id_processing',
        'order_status_id_completed',
        'order_status_id_expired',
        'order_status_id_cancelled',
        'order_status_id_error',
        'total'
    ];

    /**
     * @var string
     */
    private $gingerModuleName;

    /**
     * @var array
     */
    private $error = array();

    /**
     * @param string $gingerModuleName
     */
    public function index($gingerModuleName = 'ginger_ideal')
    {
        $this->setGingerModuleName($gingerModuleName);

        $this->language->load('payment/'.$gingerModuleName);
        $this->load->model('setting/setting');
        $this->load->model('localisation/order_status');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->updateSettings();
        }

        $data = $this->getTemplateData();
        $data = $this->prepareSettingsData($data);

        $data['breadcrumbs'] = $this->getBreadcrumbs();
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/ginger', $data));
    }

    /**
     * @return bool
     */
    protected function validate()
    {
        if (!$this->request->post[$this->getPostFieldName('api_key')]) {
            $this->error['missing_api'] = $this->language->get('error_missing_api_key');
        }

        return !$this->error;
    }

    /**
     * Method updates Ginger Payment Settings and redirects back to payment plugin page
     */
    protected function updateSettings()
    {
        $this->model_setting_setting->editSetting($this->getGingerModuleName(), $this->mapPostData());

        $this->session->data['success'] = $this->language->get('text_settings_saved');

        $this->response->redirect(
            $this->url->link('extension/payment', 'token='.$this->session->data['token'], true)
        );
    }

    /**
     * @return array
     */
    protected function getTemplateData()
    {
        return [
            'heading_title' => $this->language->get('heading_title'),
            'text_edit_ginger' => $this->language->get('text_edit_ginger'),
            'info_help_api_key' => $this->language->get('info_help_api_key'),
            'info_help_total' => $this->language->get('info_help_total'),
            'entry_ginger_api_key' => $this->language->get('entry_ginger_api_key'),
            'entry_order_completed' => $this->language->get('entry_order_completed'),
            'entry_order_new' => $this->language->get('entry_order_new'),
            'entry_order_error' => $this->language->get('entry_order_error'),
            'entry_order_expired' => $this->language->get('entry_order_expired'),
            'entry_order_cancelled' => $this->language->get('entry_order_cancelled'),
            'entry_order_processing' => $this->language->get('entry_order_processing'),
            'entry_sort_order' => $this->language->get('entry_sort_order'),
            'entry_status' => $this->language->get('entry_status'),
            'entry_ginger_total' => $this->language->get('entry_ginger_total'),
            'text_enabled' => $this->language->get('text_enabled'),
            'text_disabled' => $this->language->get('text_disabled'),
            'button_save' => $this->language->get('text_button_save'),
            'button_cancel' => $this->language->get('text_button_cancel'),
            'action' => $this->url->link(
                'payment/'.$this->getGingerModuleName(),
                'token='.$this->session->data['token'],
                true
            ),
            'cancel' => $this->url->link(
                'extension/payment',
                'token='.$this->session->data['token'],
                true
            )
        ];
    }

    /**
     * Process and prepare data for configuration page
     *
     * @param array $data
     * @return array
     */
    protected function prepareSettingsData(array $data)
    {
        foreach (static::$update_fields AS $fieldToUpdate) {
            $formPostFiled = $this->getPostFieldName($fieldToUpdate);
            if (isset($this->request->post[$formPostFiled])) {
                $data[$formPostFiled] = $this->request->post[$formPostFiled];
            } else {
                $data[$formPostFiled] = $this->config->get($this->getModuleFieldName($fieldToUpdate));
            }
        }

        if (empty($this->config->get($this->getModuleFieldName('api_key')))) {
            $data['info_message'] = $this->language->get('info_plugin_not_configured');
        }

        if (isset($this->error['missing_api'])) {
            $data['error_missing_api_key'] = $this->error['missing_api'];
        } else {
            $data['error_missing_api_key'] = '';
        }

        return $data;
    }

    /**
     * Generate configuration page breadcrumbs
     *
     * @return array
     */
    protected function getBreadcrumbs()
    {
        return [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'token='.$this->session->data['token'], true)
            ],
            [
                'text' => $this->language->get('text_payments'),
                'href' => $this->url->link('extension/payment', 'token='.$this->session->data['token'], true)
            ],
            [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('payment/'.$this->getGingerModuleName(),
                    'token='.$this->session->data['token'], true)
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getUpdateFields()
    {
        $fields = [];
        foreach (static::$update_fields AS $field) {
            $fields[] = $this->getGingerModuleName().'_'.$field;
        }
        return $fields;
    }

    /**
     * @return array
     */
    protected function mapPostData()
    {
        $postFields = [];
        foreach (static::$update_fields AS $field) {
            $postFields[$this->getModuleFieldName($field)] = $this->request->post[$this->getPostFieldName($field)];
        }

        return $postFields;
    }


    /**
     * @param string $gingerModuleName
     */
    protected function setGingerModuleName($gingerModuleName)
    {
        $this->gingerModuleName = $gingerModuleName;
    }

    /**
     * @return string
     */
    protected function getGingerModuleName()
    {
        return $this->gingerModuleName;
    }

    /**
     * @param  string $fieldName
     * @return string
     */
    protected function getModuleFieldName($fieldName)
    {
        return $this->getGingerModuleName().'_'.$fieldName;
    }

    /**
     * @param $postFieldName
     * @return string
     */
    protected function getPostFieldName($postFieldName)
    {
        return static::POST_FIELD_PREFIX.$postFieldName;
    }
}
