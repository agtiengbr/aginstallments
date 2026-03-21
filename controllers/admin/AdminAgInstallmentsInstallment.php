<?php

class AdminAgInstallmentsInstallmentController extends ModuleAdminController
{
    public function __construct()
    {

        $this->_defaultOrderBy = 'date_limit';
        $this->_defaultOrderWay = 'ASC';
        $this->bootstrap = true;
        $this->table = 'aginstallments_installment';
        $this->identifier = 'id_aginstallments_installment';
        $this->className = 'AgInstallmentsInstallment';
        $this->list_no_link = true;
        
        parent::__construct();

        $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'orders` o ON (o.id_order = a.`id_order`)';
        $this->_select .= 'o.reference AS order_reference,';        

        $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'customer` c ON (c.id_customer = o.`id_customer`)';
        $this->_select .= 'CONCAT(c.firstname, " ", c.lastname) AS customerName, ';

        $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'aginstallments_installment_group` g ON (a.id_aginstallments_installment_group = g.`id_aginstallments_installment_group`)';
        $this->_select .= "CONCAT(a.installment_number,'/', g.qty_installments) AS instNumber, ";  

        $this->_select .= "(a.value + a.fee_paid + a.interest_paid) AS TotalValue";

        $this->_where .= ' AND a.status IN (0, 1) ';
        

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
            'customerName' => [
                'title' => 'Cliente',
                'type' => 'text',
                'havingFilter' => true
            ],
            'value'     => [
                'title' => 'Valor',
                'type'  => 'price',
                'class' => 'fixed-width-xs center',
            ],
            'TotalValue' => [
                'title' => 'Valor Atualizado',
                'type' => 'price',
                'class' => 'fixed-width-xs center',
            ],
            'status'   => [
                'title'  => $this->l('Estado'),
                'color'  => 'color',
                'class' => 'fixed-width-xl',
                'filter_key' => 'a!status',
                'type' => 'select',
                'list'   => [
                    0 => 'Aguardando Pagamento',
                    1 => 'Vencido',
                ],
            ],
            'reference' =>[
                'title' => $this->l('Código de Barras'),
                'type' => 'text',
                'class' => 'fixed-width-xs center',
                'filter_key' => 'a!reference'
            ],

            'date_limit' => [
                'title' => $this->l('Vencimento'),
                'type'  => 'datetime',
                'class' => 'fixed-width-xs center',
            ]
        ];
        
       
        $this->actions = [
         'pay'
        ];

        $this->module->prepareNotifications();
    }
    /**
     * Altera a cor da barra de status coluna escrevendo algo específico para cada tipo de status
     *
     * @return null
     */ 

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
    /**
     * Função que pega o valor do token e se o pedido foi pago ou não.
     *
     * @param string  $token token do controlador
     * @param integer $id    id da parcela
     * @param string  $name  nome do custumer
     *
     * @return null
     */   
    public function displayPayLink($token = null, $id = null, $name = null)
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


    /**
     * Inicia a página
     *
     * @return null
     */ 
    public function initContent()
    {
        parent::initContent();
        if (Tools::getValue($this->identifier)) {
            $obj = $this->loadObject();
            $obj->payInstallment();

            $this->module->confirmations[] = 'Parcela paga com sucesso!';
            $this->module->saveNotifications();

            Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
        }  

        if (!$this->ajax && $this->display == '') {
              $this->content .= $this->renderModal();
              $this->context->smarty->assign(['content' => $this->content]);
        }
    }

    /**
     * Cria o modal da página modal.tpl e insere na página de carnês 
     *
     * @return null
     */    
    public function renderModal()
    {
         $tpl = $this->createTemplate('modal.tpl');         
         return $tpl->fetch();
    }   

    //Pega os dados do banco e envia para a página que os colocará nos campos de cada valor
    public function ajaxProcessMandaDadosParcela()
    {
        $idParcela = Tools::getValue("idParcela");
        $parcela =  new AgInstallmentsInstallment($idParcela);
        
        $data_atual = strtotime(date('Y-m-d'));
        $data_venc  = strtotime($parcela->date_limit);
        
        //quantidade de dias em atraso
        $dias = ($data_atual - $data_venc) / 86400;
        if ($dias < 0) {
            $dias2 = 0;
        }else{
            $dias2 = $dias;
        }
        $total = $parcela->value + $parcela->interest_paid + $parcela->fee_paid;
        $resposta = ["vencimento" => Tools::displayDate($parcela->date_limit), 'valor' => Tools::displayPrice($parcela->value), 'juros' => Tools::displayPrice($parcela->interest_paid), 'multa' => Tools::displayPrice($parcela->fee_paid), 'total' => Tools::displayPrice($total), 'dias' => $dias2];

        echo json_encode(
            array(
                'success' => true,
                'received_data' => Tools::getValue('foo'),
                'resposta' => $resposta
            )
        ); 

        exit();
    }

    /**
     * Função que pega a quantidade de dias e calcula os juros e as multas para o tanto de dias mas sem gravar no banco
     *
     * @return null
     */    
    public function ajaxProcessAlteraDias()
    {
        $idParcela = Tools::getValue("idParcela");
        $parcela =  new AgInstallmentsInstallment($idParcela);
        
        $data_atual = strtotime(date('Y-m-d'));
        $data_venc  = strtotime($parcela->date_limit);
        $dias = ($data_atual - $data_venc) / 86400;

        $novoDia = Tools::getValue("dias");

        $juros = $parcela->interest_paid / $dias;
        $novoJuros = $juros * $novoDia;
        if ($novoDia == 0) {
            $novamulta = 0;
        } else {
            $novamulta = $parcela->fee_paid;
        }
        $parcela->interest_paid = Tools::math_round($novoJuros, 2, PS_ROUND_HALF_UP);
        $respostaNovoDia = ['total' => Tools::displayPrice($parcela->value + $novamulta + $novoJuros), 'juros' => Tools::displayPrice($novoJuros),'dias' => $novoDia,'multa' => Tools::displayPrice($novamulta)];
        
        
        echo json_encode(
            array(
                'success' => true,
                'received_data' => Tools::getValue('foo'),
                'resposta' => $respostaNovoDia
            )
        );
    }

    /**
     * Função que pega os novos dados trazidos via ajax e altera o valor no banco
     *
     * @return null
     */    
    public function alteraJuros(){
        $idParcela = Tools::getValue("idParcela");
        $parcela =  new AgInstallmentsInstallment($idParcela);

        $juros = Tools::getValue("valorJuros");
        if ($juros == 0) {
            $parcela->fee_paid = 0;
        }
        $parcela->interest_paid = $juros;
        $parcela->update();
    }
    
    /**
     * Pega o arquivo JavaScript e insere na página
     *
     * @return null
     */ 
    public function setMedia($isNewTheme=false)
    {
        parent::setMedia($isNewTheme);

        $this->addJs([
            _PS_MODULE_DIR_ . $this->module->name . '/views/js/modal.js'
        ]);
        
    }
}