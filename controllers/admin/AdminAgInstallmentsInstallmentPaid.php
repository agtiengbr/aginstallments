<?php

class AdminAgInstallmentsInstallmentPaidController extends ModuleAdminController
{
    public function __construct()
    {

        $this->_defaultOrderBy = 'payment_date';
        $this->_defaultOrderWay = 'DESC';
        $this->list_no_link = true;
        $this->bootstrap = true;
        $this->table = 'aginstallments_installment';
        $this->identifier = 'id_aginstallments_installment';
        $this->className = 'AgInstallmentsInstallment';

        parent::__construct();

        $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'employee` e  ON (e.id_employee = a.`id_employee_payment`)';
        $this->_select .= 'CONCAT(e.firstname, " ", e.lastname) AS employee_name,';

        $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'orders` o ON (o.id_order = a.`id_order`)';
        $this->_select .= 'o.reference AS order_reference,';        

        $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'customer` c ON (c.id_customer = o.`id_customer`)';
        $this->_select .= 'CONCAT(c.firstname, " ", c.lastname) AS customerName, ';

        $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'aginstallments_installment_group` g ON (a.id_aginstallments_installment_group = g.`id_aginstallments_installment_group`)';
        $this->_select .= "CONCAT(a.installment_number,'/', g.qty_installments) AS instNumber, ";  

        $this->_select .= "(a.value + a.fee_paid + a.interest_paid) AS TotalValue";

        $this->_where .= ' AND a.status IN (2) ';
        

        $this->fields_list = [
            'id_aginstallments_installment' => [
                'title' => $this->l('ID'),
                'type'  => 'text',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'id_aginstallments_installment_group' =>[
                'title' => 'Carnê',
                'type' => 'int',
                'align' => 'center',
                'class' => 'fixed-width-xs center',
            ],
            'order_reference' =>[
                'title' => 'Pedido',
                'filter_key' => 'o!reference',
                'class' => 'fixed-width-xs center',
            ],
            'instNumber' =>[
                'title' => '# Parcela',
                'type' => 'text',
                'class' => 'fixed-width-xs center',
            ],
            'TotalValue' => [
                'title' => 'Valor Pago',
                'type' => 'price',
                'class' => 'fixed-width-xs center',
            ],
            'customerName' => [
                'title' => 'Cliente',
                'type' => 'text',
                'havingFilter' => true
            ],
            'employee_name' => [
                'title' => 'Funcionário',
                'hint' => 'Funcionário que registrou o pagamento',
                'type' => 'text'
            ],
            'payment_date' => [
                'title' => $this->l('Paga em'),
                'type'  => 'datetime',
                'class' => 'fixed-width-xs center',
            ],
        ];
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
            }
        }
    }
    public function displayPayLink($token = null,$id,$name = null)
    {
        $url = self::$currentIndex . '&token=' . $this->token . '&pay&' . $this->identifier . '=' . $id;        
        $parc = new AgInstallmentsInstallment($id);
        if ($parc->status == 2) {
            return ;
        }
        $tpl = $this->createTemplate('helpers/list/pay.tpl');
        $tpl->assign(['url' => $url]);
        return $tpl->fetch();
    }
    public function initContent()
    {
        parent::initContent();
        if (Tools::getValue($this->identifier)) {
            $obj = $this->loadObject();
            $obj -> payInstallment();
        }  
    }   
}