<?php

require_once _PS_MODULE_DIR_ . 'agcliente/lib/AgPaymentModule.php';
require_once _PS_MODULE_DIR_ . 'aginstallments/vendor/pic/Pic.php';
// require_once _PS_MODULE_DIR_ . 'aginstallments/vendor/tcpdf/tcpdf_import.php';
require_once _PS_MODULE_DIR_ . 'aginstallments/vendor/autoload.php';

use AgInstallments\Pic as Pic;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class BaseAgInstallments extends AgPaymentModule
{
    protected $hooks = [
        'payment',
        'paymentReturn',
        'paymentOptions',
        'displayBackOfficeHeader',
        'actionValidateOrder',
        'hookPaymentReturn',
        'displayOrderConfirmation',
        'hookDisplayOrderDetail',
        'hookPaymentOptions',
        'Header',
        'displayCustomerAccount',
        'displayOrderDetail'
    ];

    //menus do BackOffice
    protected $main_tab = 'AdminParentOrders';
    protected $tabs = [
        [
            "name"      => "Carnês",
            "className" => "aginstallments",
            "active"    => 1,
            "childs"    => [
                [
                    "active"    => 1,
                    "name"      => "Carnês",
                    "className" => "AdminAgInstallmentsInstallmentGroup",
                ],
                [
                    "active"    => 1,
                    "name"      => "Parcelas a Receber",
                    "className" => "AdminAgInstallmentsInstallment",
                ],
                [
                    "active"    => 1,
                    "name"      => "Parcelas Recebidas",
                    "className" => "AdminAgInstallmentsInstallmentPaid",
                ],
            ]
        ]
    ];

    public function __construct()
    {
        $this->name                   = 'aginstallments';
        $this->version                = '1.1.11';
        $this->bootstrap              = true;
        $this->author                 = 'AGTI';
        $this->need_instance          = 1;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7.99');

        parent::__construct();

        $this->displayName = 'Pagamento via Carnês';
        $this->description = 'Esse módulo permite a geração de carnês para as vendas de sua loja PrestaShop.';

    }

    public function install()
    {
        $r = parent::install();

        if (!$r) {
            return false;
        }

        $db_prefix = _DB_PREFIX_;
        $sqls = [];

        $sqls[] = "ALTER TABLE {$db_prefix}order_payment ADD COLUMN id_employee int default 0";
        foreach ($sqls as $sql) {
            try {
                Db::getInstance()->execute($sql);
            } catch (Exception $e) {
            }
        }

        return true;
    }

    /**
     * Restaura as configurações padrão do módulo. Esse método é chamado automaticamente
     * quando o módulo é instalado ou reinicializado. Essa não é uma função padrão do PrestaShop
     * mas sim um recurso adicional dos módulos da AGTI.
     *
     * @return null
     */
    public function resetConfig()
    {
        Configuration::updateValue('AGINSTALLMENTS_MAX_INSTALLMENTS', 12);
        Configuration::updateValue('AGINSTALLMENTS_MIN_VALUE_INSTALLMENT', 20);
        Configuration::updateValue('AGINSTALLMENTS_INTEREST_RATE', 5);
        Configuration::updateValue('AGINSTALLMENTS_INTEREST_RATE_LATE_PAYMENT', 1);
        Configuration::updateValue('AGINSTALLMENTS_FEE', 2);
        Configuration::updateValue('AGINSTALLMENTS_AUTOPAY_FIRST_INSTALL', 0);

        $this->loadMappings();
        if (Module::isInstalled('agcustomers') && Module::isEnabled('agcustomers')) {
            $this->cpf_mapping->mapsTo('cpf');
            $this->cnpj_mapping->mapsTo('cnpj');
            $this->social_name_mapping->mapsTo('company_name');
            $this->address_number_mapping->mapsTo('number');
        } elseif (Module::isInstalled('djtalbrazilianregister') && Module::isEnabled('djtalbrazilianregister')) {
            $this->cpf_mapping->mapsTo('djtalbrazilianregister');
            $this->cnpj_mapping->mapsTo('djtalbrazilianregister');
            $this->social_name_mapping->mapsTo('');
            $this->address_number_mapping->mapsTo('djtalbrazilianregister');
        }

        $existent_worker_group = AgClienteWorkerGroup::findByName('aginstallments_calc_fees');
        if (!Validate::isLoadedObject($existent_worker_group)) {
            $workerGroup = new AgClienteWorkerGroup;
            $workerGroup->group_name = 'aginstallments_calc_fees';
            $workerGroup->qty_wanted_workers = 1;
            $workerGroup->module = $this->name;
            $workerGroup->controller = 'calcFees';
            $workerGroup->active = 1;
            $workerGroup->save();
        }
    }

    /**
     * Página de Configurações
     *
     * @return string HTML da página de configuração
     */
    public function getContent()
    {
        $this->loadMappings();
        $this->updateConfigFromPost();

        $this->context->smarty->assign($this->getConfigurationFromDb());
        $this->context->smarty->assign(['module' => $this]);

        $this->context->controller->addCss($this->_path . 'views/css/admin/configuration.css');
        $this->context->controller->addJs('https://cdnjs.cloudflare.com/ajax/libs/riot/2.6.7/riot+compiler.min.js');

        $html = $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/admin/configuration.tpl');
        return $html . $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/admin/ps-tags.tpl');
    }

    /************************ MAPEAMENTOS *************************/
    public function loadMappings()
    {
        $this->cpf_mapping = new AgColumnMapping();
        $this->cpf_mapping->setData(array(
            'table_name' => 'customer',
            'configuration_name' => 'aginstallments_cpf'
        ));
        $this->cpf_mapping->addColumn('djtalbrazilianregister', 'Módulo de Cadastro Brasileiro');

        $this->cnpj_mapping = new AgColumnMapping();
        $this->cnpj_mapping->setData(array(
            'table_name' => 'customer',
            'configuration_name' => 'aginstallments_cnpj'
        ));
        $this->cnpj_mapping->addColumn('djtalbrazilianregister', 'Módulo de Cadastro Brasileiro');

        $this->social_name_mapping = new AgColumnMapping();
        $this->social_name_mapping->setData(array(
            'table_name' => 'customer',
            'configuration_name' => 'aginstallments_social_name'
        ));

        $this->address_number_mapping = new AgColumnMapping();
        $this->address_number_mapping->setData(array(
            'table_name' => 'address',
            'configuration_name' => 'aginstallments_address_number_mapping'
        ));
    }

    public function getCpfMapping()
    {
        return $this->cpf_mapping;
    }

    public function getCnpjMapping()
    {
        return $this->cnpj_mapping;
    }

    public function getSocialNameMapping()
    {
        return $this->social_name_mapping;
    }

    public function getAddressNumberMapping()
    {
        return $this->address_number_mapping;
    }

    public function getCustomerData(Customer $customer)
    {
        $document = AgColumnMapping::getCustomerDocument(
            $this->getCpfMapping(),
            $this->getCnpjMapping(),
            $this->getSocialNameMapping(),
            $customer
        );

        if ($document['cnpj'] && $document['company']) {
            return ['name' => $document['company'], 'cnpj' => $document['cnpj'], 'cpf' => $document['cpf']];
        }

        return ['name' => $document['name'], 'cpf' => $document['cpf']];
    }


    /**
     * Cria um carnê
     *
     * @param int $id_order que é o id do pedido
     * @param int $qty_installment que é a quantidade de parcelas que terá o carnê
     *
     * @return null
     */
    public function createInstallmentGroup($id_order,$qty_installment)
    {
        $entradas = $this->getConfigurationFromDb();
        $entrada = $entradas['aginstallments_autopay_first_install'];
        $taxa_de_juros2 = $this->getConfigurationFromDb();
        $taxa_de_juros = $taxa_de_juros2['aginstallments_interest_rate_late_payment'];
        $multa = $taxa_de_juros2['aginstallments_fee'];
        $order = new Order($id_order);
        $valor_compra = $order->total_paid_tax_incl;

        $valor_final = (100 + $qty_installment * $taxa_de_juros2['aginstallments_interest_rate'])/100 * $valor_compra;

        $divisao = $valor_final / $qty_installment;
        $valor_prestacoes = Tools::ps_round($divisao, 2, PS_ROUND_DOWN);

        $insert = new AgInstallmentsInstallmentGroup();
        $insert->value = $valor_final;
        $insert->details = "";
        $insert->status = 0;
        $insert->qty_installments = $qty_installment;
        $insert->interest_rate = $taxa_de_juros;
        $insert->fee_rate = $multa;
        $insert->periodicity = 0;
        $insert->value_paid = 0;
        $insert->interest_paid = $taxa_de_juros;
        $insert->fee_paid = 0;
        $insert->id_order = $id_order;

        $insert->save();

        return $insert;
    }


    /**
     * Busca as configurações do módulo salvas no banco de dados
     *
     * @return null
     */
    public function getConfigurationFromDb()
    {
        $return = [
            'aginstallments_max_installments' => Configuration::get('AGINSTALLMENTS_MAX_INSTALLMENTS'),
            'aginstallments_min_value_installment' => Configuration::get('AGINSTALLMENTS_MIN_VALUE_INSTALLMENT'),
            'aginstallments_interest_rate' => Configuration::get('AGINSTALLMENTS_INTEREST_RATE'),
            'aginstallments_interest_rate_late_payment' => Configuration::get('AGINSTALLMENTS_INTEREST_RATE_LATE_PAYMENT'),
            'aginstallments_fee' => Configuration::get('AGINSTALLMENTS_FEE'),
            'aginstallments_autopay_first_install' => Configuration::get('AGINSTALLMENTS_AUTOPAY_FIRST_INSTALL'),
            'aginstallments_logo' => $this->context->shop->getBaseURL(true) . 'modules/' . $this->name . '/views/img/logo.png'
        ];

        return $return;
    }

    /**
     * Salva as configurações do módulo quando o formulário da página de configurações é submetido
     *
     * @return null
     */
    protected function updateConfigFromPost()
    {
        if (!Tools::getIsSet('aginstallments_min_value_installment')) {
            return;
        }

        $max_installments = Tools::getValue('aginstallments_max_installments');
        if (!Validate::isInt($max_installments) || $max_installments <= 0) {
            $this->context->controller->errors[] = 'O número máximo de parcelas deve ser um número maior que zero.';
        } else {
            Configuration::updateValue('AGINSTALLMENTS_MAX_INSTALLMENTS', $max_installments);
        }

        $min_installment_value = Tools::getValue('aginstallments_min_value_installment');
        if (!Validate::isFloat($min_installment_value) || $min_installment_value < 0) {
            $this->context->controller->errors[] = 'O valor mínimo da parcela deve ser um número não negativo.';
        } else {
            Configuration::updateValue('AGINSTALLMENTS_MIN_VALUE_INSTALLMENT', $min_installment_value);
        }

        $aginstallments_interest_rate = Tools::getValue('aginstallments_interest_rate');
        if (!Validate::isFloat($aginstallments_interest_rate) || $aginstallments_interest_rate < 0) {
            $this->context->controller->errors[] = 'A taxa de juros do parcelamento deve ser um número não negativo.';
        } else {
            Configuration::updateValue('AGINSTALLMENTS_INTEREST_RATE', $aginstallments_interest_rate);
        }

        $aginstallments_interest_rate = Tools::getValue('aginstallments_interest_rate_late_payment');
        if (!Validate::isFloat($aginstallments_interest_rate) || $aginstallments_interest_rate < 0 || $aginstallments_interest_rate > 1) {
            $this->context->controller->errors[] = 'A taxa de juros por atraso deve ser um número de 0 a 1% a.m.';
        } else {
            Configuration::updateValue('AGINSTALLMENTS_INTEREST_RATE_LATE_PAYMENT', $aginstallments_interest_rate);
        }

        $aginstallments_fee = Tools::getValue('aginstallments_fee');
        if (!Validate::isFloat($aginstallments_fee) || $aginstallments_fee < 0 || $aginstallments_fee > 2) {
            $this->context->controller->errors[] = 'A multa por atraso deve ser um número de 0 a 2% a.m.';
        } else {
            Configuration::updateValue('AGINSTALLMENTS_FEE', $aginstallments_fee);
        }

        Configuration::updateValue('AGINSTALLMENTS_AUTOPAY_FIRST_INSTALL', (int) Tools::getValue('aginstallments_autopay_first_install'));

        //mapeamentos
        $this->getCpfMapping()->mapsTo(Tools::getValue('aginstallments_cpf'));
        $this->getCnpjMapping()->mapsTo(Tools::getValue('aginstallments_cnpj'));
        $this->getSocialNameMapping()->mapsTo(Tools::getValue('aginstallments_social_name'));
        $this->getAddressNumberMapping()->mapsTo(Tools::getValue('aginstallments_address_number'));

        try {
            if (isset($_FILES['aginstallments_logo'])) {
                if ($_FILES['aginstallments_logo']['error']) {
                    switch($_FILES['aginstallments_logo']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        throw new Exception('O arquivo enviado é maior do que o limite permitido pelo seu servidor de hospedagem.');
                    case UPLOAD_ERR_NO_FILE:
                        goto after_upload;
                    case UPLOAD_ERR_CANT_WRITE:
                        throw new Exception('Erro ao salvar o arquivo. Talvez você tenha atingido o limite de espaço em disco de sua hospedagem.');
                    default:
                        throw new Exception('Ocorreu um erro não esperado no envio do arquivo de imagem.');
                    }
                }

                $path = $_FILES['aginstallments_logo']['tmp_name'];

                $pic = new Pic($path);

                list($image_w, $image_h) = getimagesize($path);
                if ($image_w > $image_h) {
                    $pic->resize(['width' => '200']);
                } else {
                    $pic->resize(['height' => '200']);
                }

                $pic->save(_PS_MODULE_DIR_ . $this->name . '/views/img/logo.png');
            }
        } catch (Exception $e) {
            $this->context->controller->errors[] = $e->getMessage();
        }

        after_upload:
        if (count($this->context->controller->errors) === 0) {
            $this->context->controller->confirmations[] = "Configurações atualizadas com sucesso!";
        }
    }
    /**
     * Calcula a quantidade de juros e multa da parcela
     *
     * @param array $options Valor mínimo da parcela
     * @param int   $value_cart - Valor do carrinho
     *
     * @return array
     */
    public function calcInstallments($options, $value_cart)
    {
        $return = [];


        $max_installments = Configuration::get('AGINSTALLMENTS_MAX_INSTALLMENTS');
        for ($i = 0; $i < $max_installments; $i++) {

            $installments_interest_rate = $options['aginstallments_interest_rate'];
            $total_value = (100 + $installments_interest_rate * ($i+1))/100 * $value_cart;
            $installment_value = $total_value / ($i+1);

            if (Tools::convertPrice($installment_value, null, false) < intval($options['aginstallments_min_value_installment']) && $i) {
                break;
            }


            $return[] = array(
              'total' => Tools::displayPrice($total_value),
              'installment_value' => Tools::displayPrice($installment_value)
            );
        }
        
        return $return;
    }

    public function selectInstallmentGroupByIdOrder($id_order)
    {
        $query = new DbQuery;
        $query -> select('id_aginstallments_installment_group');
        $query->from('aginstallments_installment_group');
        $query->where('id_order ='. (int) $id_order);

        $db_data = Db::getInstance()->getValue($query);
        return $db_data;
    }

    public function getOfflinePaymentOption($params)
    {
        $embeded = new PaymentOption();
        $embeded->setCallToActionText('Pagar no Carnê')
                      ->setForm($this->renderForm($params['cart']));
                      //->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                      //->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/payment.jpg'));
        return $embeded;
    }

    public function renderForm($Cart)
    {
        $cart = new Cart($Cart->id);
        $options = $this->getConfigurationfromdb();
        $orderTotalValue = $cart->getOrderTotal();
        $qty_inst = $this->calcInstallments($options, $orderTotalValue);
        $this->smarty->assign(array(
            'qty_installments' => $qty_inst,
            'action' => $this->context->link->getModuleLink($this->name, 'makePayment', array(), true)
        ));

        return $this->display($this->name, 'views/templates/front/paymentForm.tpl');
    }

    public function getPDFUrl($token,$id)
    {
        $urlIndex = $this->context->link->getModuleLink($this->name, 'makePdf', array(), true);
        $url = $urlIndex . '?token=' . $token . '&printPDF&id_aginstallments_installment_group=' .(int) $id;

        return $url;
    }

    public static function getInstallmentGroupIdByIdOrder($id_order)
    {
        $query = new DbQuery;
        $query->select('id_aginstallments_installment_group');
        $query->from('aginstallments_installment_group');
        $query->where('id_order = '.$id_order);
        $id_installment_group = Db::getInstance()->getValue($query);
        return $id_installment_group;
    }
    
    public static function checkIfHaveInstallmentGroup($id_customer)
    {
        $query = new DbQuery;
        $query->select('payment');
        $query->from('orders');
        $query->where('id_customer = '. (int) $id_customer.' and payment = "Pagamento via Carnês"');
        $check = Db::getInstance()->getValue($query);
        if ($check != "") {
            return true;
        } else {
            return false;
        }
    }


    //************************** HOOKS *********************************/
    public function hookDisplayBackOfficeHeader($params)
    {
        //página de criação de pedido
        if (get_class($this->context->controller) === 'AdminOrdersController' && Tools::getIsSet('addorder')) {
            $this->context->controller->addJs($this->_path . 'views/js/admin/add_order.js');
        }
        if (get_class($this->context->controller) === 'AdminOrdersController' && Tools::getIsSet('vieworder')) {
            $this->context->controller->addJs($this->_path . 'views/js/admin/view_order.js');
        }

        if (get_class($this->context->controller) === 'AdminAgInstallmentsInstallmentController') {
            $this->context->controller->addJs($this->_path . 'views/js/admin/modal.js');
        }

        $id_installment_group = $this->selectInstallmentGroupByIdOrder(Tools::getValue('id_order'));

        $linkPdf = $this->context->link->getAdminLink('AdminAgInstallmentsInstallmentGroup') . "&printPDF&id_aginstallments_installment_group=".$id_installment_group;
        $this->context->controller->addCss(array(
            $this->_path . 'views/css/admin/base.css',
        ));

        $token = Tools::getAdminTokenLite('AdminAgInstallmentsInstallmentGroup');
        $tokenPay = Tools::getAdminTokenLite('AdminAgInstallmentsInstallment');
        $js = "<script type='text/javascript'>var token_AdminAginstallmentsIntallmentGroup = '".$token."'; var linkPdf = '".$linkPdf."'; var tokenInstallments = '". $tokenPay . "'</script>";


        return $js;

    }

    public function hookActionValidateOrder($param)
    {
        $qtyInstallments = Tools::getValue("select_installment");
        $order = $param['order'];

        if ($order->module == "aginstallments" || ($order->module == 'agpdv' && $order->payment == 'Pagamento via Carnês')) {
            $this->createInstallmentGroup($param['order']->id, $qtyInstallments);
        }
    }

    public function hookPaymentOptions($params)
    {
        $payment_options = [
            $this->getOfflinePaymentOption($params)
        ];
        return $payment_options;
    }

    public function hookHeader()
    {
        $this->context->controller->addCss(array(
            $this->_path . 'views/css/front/paymentForm.css',
            $this->_path . 'views/css/front/linkPage.css'
        ));
    }

    public function hookDisplayOrderConfirmation($params)
    {
        if($params['order']->module != 'aginstallments') {
            return false;
        }
        $token = Tools::getToken();
        
        /** @var Order */
        $order = $params['order'];
        $installmentGroup = AgInstallmentsInstallmentGroup::findByOrder($order);
        $url = $this->getPDFUrl($token, $installmentGroup->id);

        $this->smarty->assign([
            'link' => $url
        ]);
        $html = $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/front/linkPage.tpl');

        return $html;
    }

    public function hookDisplayCustomerAccount()
    {
        $link = $this->context->link->getModuleLink($this->name, 'listInstallments');
        $userId = $this->context->customer->id;
        if (self::checkIfHaveInstallmentGroup($userId) == true) {
            $element = '<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="identity-link" href="'.$link . '"> <span class="link-item"> <i class="material-icons">assignment</i>'.$this->l("Carnês").' </span> </a>';
            return $element;
        } else {
            return false;
        }
    }

    public function hookDisplayOrderDetail()
    {
        $id_order = intval(Tools::getValue('id_order'));
        $id_installments_group = self::getInstallmentGroupIdByIdOrder($id_order);
        $installments = AgInstallmentsInstallment::getInstallmentsFromInstallmentGroup($id_installments_group);
        $this->context->controller->addCss(_PS_MODULE_DIR_ . 'aginstallments/views/css/front/listInstallments.css');
        if (!$id_installments_group) {
            return false;
        } else {
            $link = $this->context->link->getModuleLink($this->name, 'listInstallments');
            $link = $link . '?id_installment_group=' . $id_installments_group . '&pay=1&generatePdf=1&id_installment_group='.$id_installments_group;
            $this->smarty->assign([
                'linkPdf' => $link,
                'installments' => $installments
            ]);
        }
        return $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/front/installmentsList.tpl');

    }
}
