<?php

class AgInstallmentsInstallment extends AgObjectModel
{
    public static $definition = array(
        'table' => 'aginstallments_installment',
        'primary' => 'id_aginstallments_installment',
        'multilang' => false,
        'fields' => [
            'id_aginstallments_installment'   => ['type' => self::TYPE_INT,   'validate' => 'isInt'],
            'value'                               => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'db_type' => 'float', 'required' => true],
            'date_limit'                          => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'db_type'  => 'datetime'],
            'status'                              => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type'  => 'tinyint', 'required' => true],
            'reference'                           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'db_type' => 'varchar(64)', 'required' => true],
            'payment_date'                        => ['type' => self::TYPE_DATE, 'db_type'  => 'datetime'],
            'id_employee_payment'                 => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type'  => 'int'],
            'value_paid'                          => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'db_type' => 'float'],
            'interest_paid'                       => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'db_type' => 'float'],
            'fee_paid'                            => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'db_type' => 'float'],
            'id_aginstallments_installment_group' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type'  => 'int', 'required' => true],
            'id_order'                            => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type'  => 'int', 'required' => true],
            'installment_number'                  => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type'  => 'int', 'required' => true],
        ],
        'indexes' => [
            [
                'fields' => ['reference'],
                'prefix' => 'unique',
                'name'   => 'unique_reference'
            ]
        ]
    );

    public $id_aginstallments_installment_group;
    public $id_aginstallments_installment;
    public $value;
    public $date_limit;
    public $status;
    public $reference;
    public $payment_date;
    public $id_employee_payment;
    public $value_paid;
    public $interest_paid;
    public $fee_paid;
    public $id_order;
    public $installment_number;
    /**
     * Busca uma parcela pelo código de barras
     *
     * @param String $reference código de barras da parcela
     *
     * @return AgInstallmentsInstallment Parcela que contém o código de barras ou objeto não carregado caso a parcela não exista
     */ 
    public static function pegaReferencia($reference)
    {
        $query = new DbQuery;    

        $query->from('aginstallments_installment');
        $query->select('id_aginstallments_installment');
        $query->where('reference ="'. pSQL($reference) .'"');

        $id = Db::getInstance()->getValue($query);

        if ($id) {
            return new AgInstallmentsInstallment($id);
        } else {
            return new AgInstallmentsInstallment;
        }
    }


    /**
     * Calcula o valor das multas e dos juros se as parcelas ainda não foram pagas
     *
     * @return null
     */ 
    public function calcInterestAndFee()
    {
        //não cobra juros nem multa se a parcela já estiver paga
        if ($this->status == 2) {
            return ['multa' => 0,'juros'=>0];
        }

        $data_atual = strtotime(date('Y-m-d'));
        $data_venc  = strtotime($this->date_limit);
        
        //quantidade de dias em atraso
        $dias = ($data_atual - $data_venc) / 86400;
        $mes = Tools::ps_round(($dias / 30), 0, PS_ROUND_UP);       

        if ($dias > 0) {
            //obtém a taxa de juros/multa do carnê
            $carne = new AgInstallmentsInstallmentGroup($this->id_aginstallments_installment_group);

            $multa = $this->value * ($carne->fee_rate / 100);
            $juros = ($this->value * ($carne->interest_rate / 100)/30) * $dias;

            $mostrar = ['multa' => $multa,'juros'=>$juros];
            $this->fee_paid = $this->fee_paid + $multa;
            $this->interest_paid = Tools::math_round($this->interest_paid + $juros, 2, PS_ROUND_UP);
            $this->update();
            
        } else {
            return ['multa' => 0,'juros'=>0];
        }
        
    }



    /**
     * Marca a parcela como paga calculando seus juros e multa caso esteja atrasada
     *
     * @return null
     */ 
    public function payInstallment()
    {
        if ($this->status == 1 or $this->status == 0) {
            
            $this->payment_date = date('Y-m-d H:i:s');

            $context = Context::getContext();
            $this->id_employee_payment = $context->employee->id;
            $this->value_paid = $this->value + $this->interest_paid + $this->fee_paid;    

            $this->status = 2;
            

            $carne = new AgInstallmentsInstallmentGroup($this->id_aginstallments_installment_group);
            $carne->value_paid = $carne->value_paid + $this->value_paid;

            $carne->fee_paid = $carne->fee_paid + $this->fee_paid;
            $carne->interest_paid = $carne->interest_paid + $this->interest_paid + $this->fee_paid;
            
            
            $context = Context::getContext();
            $order = new Order($this->id_order);

            //atribui uma fatura ao pedido se ele ainda não tiver fatura
            $order->setInvoice();

            //obtém o objeto do tipo OrderInvoice referente à fatura do pedido
            $order_invoice = OrderInvoice::getInvoiceByNumber($order->invoice_number);

            //gera um novo pagamento para o pedido e o registra na tabela do PrestaShop
            $orderPay = new OrderPaymentCore;
            $orderPay->order_reference = $order->reference;
            $orderPay->id_currency = $context->currency->id;
            $orderPay->amount = $this->value + $this->interest_paid + $this->fee_paid;
            $orderPay->payment_method = "Pagamento via Carnês";
            $orderPay->transaction_id = $this->reference;// código de barras da parcela
            $orderPay->date_add = date("Y-m-d");
    
            $orderPay ->save();

            //salva o ID do funcionário que acabou de ser criado
            Db::getInstance()->update('order_payment', ['id_employee' => $context->employee->id], 'id_order_payment=' . (int)$orderPay->id);

            //cria o vínculo entre o pagamento que acabou de ser criado, a fatura do pedido e o pedido em si
            Db::getInstance()->insert('order_invoice_payment', ['id_order' => (int)$order->id, 'id_order_payment' => (int)$orderPay->id, 'id_order_invoice' => $order_invoice->id]);

            $carne->update();
            $this->updateInstallmentsGroup();
            $this->update();
        }
        //@todo atualizar o valor pago do carnê!
        
    }


    /**
     * Retorna todas as parcelas de um carnê
     *
     * @param int $id_aginstallments_installment_group ID do carnê
     *
     * @return array dados das parcelas conforme armazenados no banco
     */ 
    public static function getInstallmentsFromInstallmentGroup($id_aginstallments_installment_group)
    {
        $query = new DbQuery;

        $query->from('aginstallments_installment');
        $query->where('id_aginstallments_installment_group ='. (int) $id_aginstallments_installment_group);

        $db_data = Db::getInstance()->executeS($query);
        
        return $db_data;
    }

    public static function getAllInstallments()
    {
        $query = new DbQuery;

        $query->from('aginstallments_installment');

        $db_data = Db::getInstance()->executeS($query);
        
        return $db_data;   
    }

    /**
     * Retorna todas as parcelas pagas de um carnê
     *
     * @param int $id_aginstallments_installment_group ID do carnê
     *
     * @return array dados das parcelas conforme armazenados no banco
     */ 
    public static function getPaidInstallmentsFromInstallmentGroup($id_aginstallments_installment_group)
    {
        $query = new DBQuery;

        $query->from('aginstallments_installment');
        $query->where('status = 2');
        $query->where('id_aginstallments_installment_group ='. (int) $id_aginstallments_installment_group);

        $db_data = Db::getInstance()->executeS($query);
        
        return $db_data;
    }

    /**
     * Retorna todas as parcelas atrasadas de um carnê
     *
     * @param int $id_aginstallments_installment_group ID do carnê
     *
     * @return array dados das parcelas conforme armazenados no banco
     */ 
    public static function getDelayedInstallmentsFromInstallmentGroup($id_aginstallments_installment_group)
    {
        $query = new DBQuery;

        $query->from('aginstallments_installment');
        $query->where('status = 1');
        $query->where('id_aginstallments_installment_group ='. (int) $id_aginstallments_installment_group);

        $db_data = Db::getInstance()->executeS($query);
        
        return $db_data;
    }


    /**
     * Modifica os carnês que foram pagos
     *
     * @return null
     */ 
    public function updateInstallmentsGroup()
    {
        $paid_installments = self::getPaidInstallmentsFromInstallmentGroup($this->id_aginstallments_installment_group);
        $all_installments  = self::getInstallmentsFromInstallmentGroup($this->id_aginstallments_installment_group);

        $carne = new AgInstallmentsInstallmentGroup($this->id_aginstallments_installment_group);
        $order = new Order($carne->id_order);
        if (count($paid_installments) == count($all_installments)-1) {  
            $carne->status = 2;        
            $order->setCurrentState(2);

        } else {
            $delayed_installments = self::getDelayedInstallmentsFromInstallmentGroup($this->id_aginstallments_installment_group);

            if (count($delayed_installments) > 0) {
                $carne->status = 1;
                $order->setCurrentState(1);
            }
        }        
        $carne->update();
        $order->update();
    }  

    /**
     * Pega todas as percelas que estão em atraso e as deixa como atrasadas
     *
     * @return null
     */    
    public static function markDelayedInstallments()
    {
        $query = new DbQuery;

        $query->from('aginstallments_installment');
        $query->where('status = 0');
        $query->where('date_limit < "' . date('Y-m-d H:i:s') .'"');
        
        $db_data = Db::getInstance()->executeS($query);
        $conta_registros = count($db_data);

        $i = 0;       
        while ($i < $conta_registros) {
            $installment = new AgInstallmentsInstallment($db_data[$i]['id_aginstallments_installment']);

            $installment->status = 1;
            $installment->calcInterestAndFee();


            $installment->update();
            $i++;
        }
    }

    /**
     * Essa função deixa os carnês que estão atrasados como atrasados
     *
     * @return null
     */
    public static function markDelayedInstallmentsGroup()
    {
        $query = new DbQuery;

        $query->from('aginstallments_installment');
        $query->where('status = 1');

        $atrasado = Db::getInstance()->executeS($query);
        $conta_registros = count($atrasado);

        $i = 0;
        while ($i < $conta_registros) {
            $installment_group = new AgInstallmentsInstallmentGroup($atrasado[$i]['id_aginstallments_installment_group']);
            $installment_group->status = 1;
            // $installment_group->payment_date = null;/
            $installment_group->update();
            $i++;
        }
    }
}
    
    

   

