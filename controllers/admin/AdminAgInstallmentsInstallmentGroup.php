<?php
class AdminAgInstallmentsInstallmentGroupController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'aginstallments_installment_group';
        $this->identifier = 'id_aginstallments_installment_group';
        $this->className = 'AgInstallmentsInstallmentGroup';

        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $this->module->prepareNotifications();

        $this->_join .= ' INNER JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order=a.id_order';
        $this->_select .= 'o.reference';

        $this->_join .= ' INNER JOIN ' . _DB_PREFIX_ . 'customer c ON o.id_customer=c.id_customer';
        $this->_select .= ', CONCAT(c.firstname, " ", c.lastname) customer_name';

        $this->fields_list = [
            'id_aginstallments_installment_group' => [
                'title' => $this->l('ID'),
                'type'  => 'text',
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ],
            'customer_name' => [
                'title' => 'Comprador',
                'type' => 'text',
                'havingFilter' => true
            ],
            'reference' => [
                'title' => 'Pedido',
                'type' => 'text',
                'class' => 'fixed-width-xs'
            ],
            'status'   => [
                'title' => $this->l('Estado'),
                'type' => 'select',
                'filter_key' => 'a!status',

                'align' => 'text-center',
                'color' => 'color',
                'class' => 'fixed-width-xs',
                'list'  => [
                    0 => 'Aguardando Pagamento',
                    1 => 'Vencido',
                    2 => 'Pago',
                    3 => 'Cancelado'
                ],
            ],
            'value'     => [
                'title' => $this->l('Valor'),
                'type'  => 'price',
                'class' => 'fixed-width-xs center'
            ],
            'value_paid' =>[
                'title'=>'Pago',
                'type'=>'price',
                'class' => 'fixed-width-xs center'
            ],
            'qty_installments' => [
                'title' => $this->l('# Parcelas'),
                'type' => 'number',
                'class' => 'fixed-width-xs center'
            ],
            'date_add' => [
                'title' => 'Criado Em',
                'type'  => 'datetime',
                'class' => 'fixed-width-lg center',
                'filter_key' => 'a!date_add'
            ],
            'date_upd' => [
                'title' => 'Modificado Em',
                'type'  => 'datetime',
                'class' => 'fixed-width-lg center',
                'filter_key' => 'a!date_upd'
            ],
        ];

        $this->actions = ['printPDF', 'cancel'];

    }


    public function initContent()
    {
        parent::initContent();

        if (Tools::getIsSet('printPDF') && Tools::getValue($this->identifier)) {
            if (!$this->viewAccess()) {
                $this->errors[] = $this->trans('You do not have permission to view this.', array(), 'Admin.Notifications.Error');
                return;
            }

            $obj = $this->loadObject();
            $obj->generatePdf($this->module->getConfigurationFromDb(), $this->module);

            exit();
        } elseif (Tools::getIsSet('cancel') && Tools::getValue($this->identifier)) {
            if (!$this->viewAccess()) {
                $this->errors[] = 'Você não tem permissão para editar um carnê.';
                return;
            }

            try {
                $obj = $this->loadObject();
                $obj->cancel();
                $this->module->confirmations[] = 'Carnê cancelado com sucesso!';
            } catch (Exception $e) {
                $this->module->errors[] = $e->getMessage();
            }

            $this->module->saveNotifications();

            Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
        }
    }

    public function setMedia($isNewTheme=false)
    {
        parent::setMedia($isNewTheme);

        if (!Tools::getIsSet($this->identifier)) {
            $this->context->controller->addJs(_PS_MODULE_DIR_ . $this->module->name . '/views/js/admin/ag_installments_installment_group/list.js');
        }
    }

    public function getList($id_lang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $id_lang_shop = null)
    {
        parent::getList($id_lang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $id_lang_shop = null);
        $nb = count($this->_list);
        for ($i = 0; $i < $nb; $i++) {
            switch($this->_list[$i]['status']) {
            case 0:
                $this->_list[$i]['status'] = 'Aguardando Pagamento';
                $this->_list[$i]['color'] = '#0000ff';
                break;
            case 1:
                $this->_list[$i]['status'] = 'Vencido';
                $this->_list[$i]['color'] = '#ff0000';
                break;
            case 2:
                $this->_list[$i]['status'] = 'Pago';
                $this->_list[$i]['color'] = '#008000';
                break;
            case 3:
                $this->_list[$i]['status'] = 'Cancelado';
                $this->_list[$i]['color'] = '#FF0000';
                break;
            }
        }
    }

    // ***************************** Funções AJAX **************************

    /**
     * Pega os valores do banco e envia para a função de criação de parcelas
     *
     * @return null
     */
    public function ajaxProcessCalcInstallments()
    {
        $options = $this->module->getConfigurationfromdb();

        $cart = new Cart(Tools::getValue('id_cart'));
        $val = $cart->getOrderTotal();
        $valueCart = floatval($val);

        $discount = Tools::getValue('discount');
        $valueCart -= $discount;

        $resposta = $this->module->calcInstallments($options, $valueCart);

        echo json_encode(array(
            'success' => true,
            'resposta' => $resposta
        ));
        exit();
    }

    // ***************************** Ações individuais *********************
    /**
     * Calcula o valor das multas e dos juros se as parcelas ainda não foram pagas
     *
     * @param string  $token Token do controlador atual
     * @param integer $id    id da parcela
     *
     * @return null
     */
    public function displayPrintPDFLink($token, $id)
    {
        if (!$this->viewAccess()) {
            return;
        }

        $obj = new AgInstallmentsInstallmentGroup($id);
        if (!in_array($obj->status, [0, 1])) {
            return;
        }

        $url = self::$currentIndex . '&token=' . $this->token . '&printPDF&' . $this->identifier . '=' . $id;

        $tpl = $this->createTemplate('helpers/list/print_pdf.tpl');
        $tpl->assign(['url' => $url]);

        return $tpl->fetch();
    }


    public function displayCancelLink($token, $id)
    {
        if (!$this->access('edit', false)) {
            return;
        }

        $obj = new AgInstallmentsInstallmentGroup($id);
        if (!in_array($obj->status, [0, 1])) {
            return;
        }

        $url = self::$currentIndex . '&token=' . $this->token . '&cancel&' . $this->identifier . '=' . $id;

        $tpl = $this->createTemplate('helpers/list/cancel.tpl');
        $tpl->assign(['url' => $url]);

        return $tpl->fetch();
    }
}
