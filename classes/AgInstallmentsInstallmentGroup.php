<?php

class AgInstallmentsInstallmentGroup extends AgObjectModel
{
    public static $definition = array(
        'table' => 'aginstallments_installment_group',
        'primary' => 'id_aginstallments_installment_group',
        'multilang' => false,
        'fields' => [
            'id_aginstallments_installment_group' => ['type' => self::TYPE_INT,   'validate' => 'isInt'],
            'value'            => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'db_type' => 'float', 'required' => true],
            'details'          => ['type' => self::TYPE_HTML,  'validate' => 'isPrice', 'db_type' => 'text'],
            'date_add'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'db_type'  => 'datetime'],
            'date_upd'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'db_type'  => 'datetime'],
            'status'           => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type'  => 'tinyint', 'required' => true],
            'qty_installments' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type'  => 'tinyint', 'required' => true],
            'interest_rate'    => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'db_type' => 'float'],
            'fee_rate'         => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'db_type' => 'float'],
            'periodicity'      => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type'  => 'tinyint', 'required' => true],
            'value_paid'       => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'db_type' => 'float'],
            'interest_paid'    => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'db_type' => 'float'],
            'fee_paid'         => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'db_type' => 'float'],
            'id_order'         => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type'  => 'int', 'required' => true],
        ]
    );

    public $id_aginstallments_installment_group;
    public $value;
    public $details;
    public $date_add;
    public $date_upd;
    public $status;
    public $qty_installments;
    public $interest_rate;
    public $fee_rate;
    public $periodicity;
    public $value_paid;
    public $interest_paid;
    public $fee_paid;
    public $id_order;

    /**
     * @return AgInstallmentsInstallmentGroup
     */
    public static function findByOrder(Order $order)
    {
        $sql = new DbQuery;
        $sql->from('aginstallments_installment_group')
            ->where('id_order=' . (int)$order->id);

        $db_data = Db::getInstance()->getRow($sql);
        $obj = new AgInstallmentsInstallmentGroup(@$db_data['id_aginstallments_installment_group']);
        return $obj;
    }


    /**
     * Adiciona parcelas a um carnê
     *
     * @param bool $auto_date   preenche os campos date_add e date_upd automaticamente
     * @param bool $null_values preenche os campos não informados com NULL automaticamente
     *
     * @return null
     */
    public function add($auto_date = true, $null_values= false)
    {
        $r = parent::add($auto_date = true, $null_values = false);
        if ($r == false) {
            return false;
        } else {
            $i = 0;

            //insere as parcelas no carnê
            while ($i < $this->qty_installments) {
                $pull_installment = new AgInstallmentsInstallment();

                $installment_value = $this->value / $this->qty_installments;
                $installment_value = Tools::ps_round($installment_value, 2, PS_ROUND_DOWN);

                //tratamento para o caso em que o valor das parcelas seja uma dízima
                //caso os valores das parcelas não possam ser todos iguais, adiciona o valor faltante à última parcela
                if ($i == $this->qty_installments-1) {
                    $installments_value_total = $installment_value * $this->qty_installments;

                    if ($installments_value_total < $this->value) {
                        $last_installment_value = $this->value - $installments_value_total;
                        $pull_installment->value = $installment_value + Tools::ps_round($last_installment_value, 2, PS_ROUND_DOWN);
                    } else {
                        $pull_installment->value = $installment_value;
                    }
                } else {
                    $pull_installment->value = $installment_value;
                }


                //obtém a data de vencimento da primeira parcela: se o pagamento for com entrada, o vencimento é o dia atual
                //caso contrário é para 1 mês
                if (Configuration::get('AGINSTALLMENTS_AUTOPAY_FIRST_INSTALL') != 0) {
                    $data = date('Y-m-d', strtotime('+'. ($i) .'month'));
                    $diaDaSemana = date('w', strtotime($data));
                    if ($diaDaSemana == 0) {
                        $novaData = date('Y-m-d', strtotime('+'. ($i) .'month +1 day'));
                    } elseif ($diaDaSemana == 6) {
                        $novaData = date('Y-m-d', strtotime('+'. ($i) .'month +2 days'));
                    } else {
                        $novaData = $data;
                    }
                    $pull_installment->date_limit = $novaData;
                } else {
                    $data = date('Y-m-d', strtotime('+'. ($i + 1) .'month'));
                    $diaDaSemana = date('w', strtotime($data));
                    if ($diaDaSemana == 0) {
                        $novaData = date('Y-m-d', strtotime('+'. ($i+1) .'month +1 day'));
                    } elseif ($diaDaSemana == 6) {
                        $novaData = date('Y-m-d', strtotime('+'. ($i+1) .'month +2 days'));
                    } else {
                        $novaData = $data;
                    }
                    $pull_installment->date_limit = $novaData;
                }

                //gera a referência única da parcela
                $pull_installment->status = 0;
                $pull_installment->installment_number = $i + 1;

                loop:
                    $reference = Tools::passwdGen(16, 'NUMERIC');
                $obj = AgInstallmentsInstallment::pegaReferencia($reference);

                //se já houver uma parcela com a referência gerada, gera uma nova referência
                if (Validate::isLoadedObject($obj)) {
                    goto loop;
                }

                $pull_installment->reference = $reference;
                $pull_installment->id_order = $this->id_order;
                $pull_installment->id_aginstallments_installment_group = $this->id;
                try {
                    $pull_installment->save();
                } catch (PrestaShopDatabaseException $e) {
                    //se já houver uma parcela com a referência gerada, gera uma nova referência
                    $texto = '/(Duplicate entry)*(unique_reference)/';
                    if (preg_match($texto, $e->getMessage())) {
                        goto loop;
                    } else {
                        echo 'não achou';
                    }
                }
                $i++;
            }
        }
    }
    /**
     * Pega os dados de cada parcela
     *
     * @return array
     */
    public function generatePdfData()
    {
        $order = new Order($this->id_order);
        $customer = new Customer($order->id_customer);

        $module = new aginstallments;
        $module->loadMappings();
        $customer_data = $module->getCustomerData($customer);

        $customer_name = $customer_data['name'];

        $context = Context::getContext();
        $nome_loja = $context->shop->name;
        $end = new Address($order->id_address_invoice);
        $endereco = $end->address1." ".$end->address2;

        return [
            'name' => $customer_name,
            'document'  => @$customer_data['cnpj']? $customer_data['cnpj'] : $customer_data['cpf'],
            'address' => $endereco,
            'shop_name' => $nome_loja
        ];
    }

    /**
     * Cria um PDF de boleto através dos dados obtidos pela função generatePdfData
     *
     * @param object $banco_loja Pega a foto da Loja
     *
     * @return null
     */
    public function generatePdf($banco_loja, AgInstallments  $module)
    {
        require_once _PS_MODULE_DIR_ . 'aginstallments/vendor/tcpdf/tcpdf_import.php';
        $i = 1;
        $soma = 20;
        if ($module->ps16) {
                require_once PS_TCPDF_PATH . 'tcpdf.php';
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            } else {
                // require_once PS_TCPDF_PATH . 'tcpdf.php';
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            }

        $dados = $this->generatePdfData();

        $img = $banco_loja['aginstallments_logo'];
        $id = 1;
        $w = 40;
        $h = 30;
        $date_ada = explode(" ", $this->date_add, 2);
        $deta = Tools::displayDate($date_ada[0]);
        $valore = Tools::displayPrice($this->value);

        while ($i <= $this->qty_installments) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $is = 0;
            $soma = 20;
            $somaP = 8;
            $pdf -> addPage();
            if ($i == 1) {
                $pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 1, 'color' => array(0, 0, 0)));
                $pdf -> Line(0.0, 14.0, 210.0, 14.0);
                $pdf -> Line(0.0, 105.0, 210.0, 105.0);
                $pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
                $pdf -> Line(5.0, $somaP + 10.0, 205.0, $somaP + 10.0);
                $pdf -> Line(5.0, $somaP + 92.0, 205.0, $somaP + 92.0);
                $pdf -> Line(5.0, $somaP + 10.0, 5.0, $somaP + 92.0);
                $pdf -> Line(205.0, $somaP + 10.0, 205.0, $somaP + 92.0);

                $pdf -> setXY(8, $somaP + 10);
                $pdf -> setFont("Helvetica", '', 14);
                $order = new Order($this->id_order);
                $pdf -> Cell(10, 10, "Carnê da Loja $dados[shop_name] referente ao pedido {$order->reference}");
                $pdf -> setXY(177, $somaP +  10);
                $pdf -> setFont("Helvetica", '', 14);
                $pdf -> Cell(10, 10, "$deta");
                $pdf -> setXY(75, $somaP + 24);


                //obtém as dimensões da imagem do logo das parcelas
                $max_width = 60;
                $max_height = 55;

                list($image_w, $image_h) = getimagesize($img);

                //verifica quantas vezes o comprimento e a altura da imagem são maiores do que o valor máximo
                $w_ratio = $image_w / $max_width;
                $h_ratio = $image_h / $max_height;

                //obtém o valor máximo pelo qual a largura/altura da imagem serão redimensionados
                $ratio = max($w_ratio, $h_ratio);

                //novas dimensões do logo
                $image_w /= $ratio;
                $image_h /= $ratio;

                //centraliza o logo
                $x0 = 10;
                $y0 = $somaP + 22;

                $x1 = $x0 + $max_width;
                $y1 = $y0 + $max_height;

                $dx = ($x1 - $x0) - $image_w;
                $dy = ($y1 - $y0) - $image_h;

                $x = $x0 + $dx/2;
                $y = $y0 + $dy/2;

                $pdf->Image("$img", $x, $y, $image_w, $image_h, '', '', '', 2);

                $pdf -> Line(10, $somaP + 20, 205, $somaP + 20);
                $pdf -> setXY(75, $somaP + 18);
                $pdf -> setFont("Helvetica", '', 8);
                $pdf -> Cell(10, 10, "Nome do Comprador:");
                $pdf -> setXY(75, $somaP + 24);
                $pdf -> setFont("Helvetica", '', 12);
                $pdf -> Cell(10, 10, "$dados[name] $dados[document]");
                $pdf -> Line(75, $somaP + 32, 200, $somaP + 32);

                $pdf -> setXY(75, $somaP + 30);
                $pdf -> setFont("Helvetica", '', 8);
                $pdf -> Cell(10, 10, "Nº de Parcelas:");
                $pdf -> setXY(75, $somaP + 34);
                $pdf -> setFont("Helvetica", '', 12);
                $pdf -> Cell(10, 10, "$this->qty_installments");
                $pdf -> Line(75, $somaP + 42, 200, $somaP + 42);
                $pdf -> setXY(75, $somaP + 39);
                $pdf -> setFont("Helvetica", '', 8);
                $pdf -> Cell(10, 10, "Valor do Carnê:");
                $pdf -> setXY(75, $somaP + 44);
                $pdf -> setFont("Helvetica", '', 12);
                $pdf -> Cell(10, 10, "$valore");
                $pdf -> Line(75, $somaP + 52, 200, $somaP + 52);
                $pdf -> setXY(75, $somaP + 49);
                $pdf -> setFont("Helvetica", '', 8);
                $pdf -> Cell(10, 10, "Multa de atraso:");
                $pdf -> setXY(75, $somaP + 54);
                $pdf -> setFont("Helvetica", '', 10);
                $pdf -> Cell(10, 10, "Em caso de atraso cobrar multa de ".$this->fee_rate."%");
                $pdf -> Line(75, $somaP + 62, 200, $somaP + 62);
                $pdf -> setXY(75, $somaP + 59);
                $pdf -> setFont("Helvetica", '', 8);
                $pdf -> Cell(10, 10, "Juros de atraso:");
                $pdf -> setXY(75, $somaP + 64);
                $pdf -> setFont("Helvetica", '', 9);
                $pdf -> Cell(10, 10, "Em caso de atraso cobrar juros de ".$this->interest_rate."% ao dia");
                $pdf -> Line(75, $somaP + 72, 200, $somaP + 72);

                $mostrar = 1;

                $soma = 110;
            } else {
                $mostrar = 0;
                $soma = 20;
            }

            if ($i == 1) {
                $repet = 2;
            } else {
                $pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 1, 'color' => array(0, 0, 0)));
                $pdf -> Line(0.0, 15.0, 210.0, 15.0);
                $repet = min(3, $this->qty_installments);
            }


            while ($is < $repet && $id <= $this->qty_installments) {
                $pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
                $parcela = AgInstallmentsInstallment::getInstallmentsFromInstallmentGroup($this->id_aginstallments_installment_group);
                $installment_number = $parcela[$id-1]['installment_number'];
                $referencia = $parcela[$id-1]['reference'];
                $data_limite = explode(" ", $parcela[$id-1]['date_limit'], 2);

                $multa = Tools::displayPrice($parcela[$id-1]['value'] * ($this->fee_rate/100));
                $juros = Tools::displayPrice($parcela[$id-1]['value'] * ($this->interest_rate/100));

                
                $valor = Tools::displayPrice($parcela[$id-1]['value']);
                $data = Tools::displayDate($data_limite[0]);
                $generator = new Picqer\Barcode\BarcodeGeneratorJPG();

                file_put_contents(_PS_MODULE_DIR_ . 'aginstallments/views/img/barcode/' . "cod_".$id.".jpg", $generator->getBarcode("$referencia", $generator::TYPE_CODE_128));

                $pdf->setJPEGQuality(75);
                $pdf -> Line(5.0, $soma + 0.0, 205.0, $soma + 0.0);
                $pdf -> setXY(10, $soma + 1);
                $pdf -> setFont('Helvetica', '', 12);

                $order = new Order($this->id_order);

                $order = new Order($this->id_order);
                $pdf -> Cell(50, 10, "Pedido: {$order->reference} | Número do Carnê: $this->id_aginstallments_installment_group");
                $pdf -> setXY(155, $soma + 1);
                $pdf -> setFont('Helvetica', '', 14);
                $pdf -> Cell(80, 10, "$referencia");
                $pdf -> Line(5.0, $soma + 9.0, 205.0, $soma + 9.0);

                //obtém as dimensões da imagem do logo das parcelas
                $max_width = 40;
                $max_height = 28;

                list($image_w, $image_h) = getimagesize($img);

                //verifica quantas vezes o comprimento e a altura da imagem são maiores do que o valor máximo
                $w_ratio = $image_w / $max_width;
                $h_ratio = $image_h / $max_height;

                //obtém o valor máximo pelo qual a largura/altura da imagem serão redimensionados
                $ratio = max($w_ratio, $h_ratio);

                //novas dimensões do logo
                $image_w /= $ratio;
                $image_h /= $ratio;

                //centraliza o logo
                $x0 = 10;
                $y0 = $soma + 11;

                $x1 = $x0 + $max_width;
                $y1 = $y0 + $max_height;

                $dx = ($x1 - $x0) - $image_w;
                $dy = ($y1 - $y0) - $image_h;

                $x = $x0 + $dx/2;
                $y = $y0 + $dy/2;

                $pdf->Image("$img", $x, $y, $image_w, $image_h, '', '', '', 2);


                $pdf -> setXY(55, $soma + 8);
                $pdf -> setFont('Helvetica', '', 8);
                $pdf -> Cell(80, 10, "Nome da Loja", 0);
                $pdf -> setXY(55, $soma + 14);
                $pdf -> setFont('Helvetica', '', 12);
                $pdf -> Cell(30, 10, "$dados[shop_name]", 0);
                $pdf -> Line(55.0, $soma + 22.0, 205.0, $soma + 22.0);

                $pdf -> setFont('Helvetica', '', 8);
                $pdf -> setXY(55, $soma + 20);
                $pdf -> Cell(80, 10, "Nome do Cliente:", 0);
                $pdf -> setXY(55, $soma + 24);
                $pdf -> setFont('Helvetica', '', 12);
                $pdf -> Cell(30, 10, "$dados[name] $dados[document]", 0);
                $pdf -> Line(55.0, $soma + 32.0, 160.0, $soma + 32.0);

                $pdf -> setXY(55, $soma + 30);
                $pdf -> setFont('Helvetica', '', 8);
                $pdf -> Cell(80, 10, "Endereço:", 0);
                $pdf -> setXY(55, $soma + 34);
                $pdf -> setFont('Helvetica', '', 12);
                $pdf -> Cell(30, 10, $dados['address'], 0);
                $pdf -> Line(55.0, $soma + 42.0, 205.0, $soma + 42.0);

                $pdf -> setXY(20, $soma + 60);
                $pdf->Image(_PS_MODULE_DIR_ . 'aginstallments/views/img/barcode/' . "cod_".$id.".jpg", 20, $soma + 60, 100, 15, 'JPG', '', '', 2);
                $pdf -> Line(5.0, $soma + 82.0, 205.0, $soma + 82.0);
                $pdf -> Line(5.0, $soma + 0.0, 5.0, $soma + 82.0);
                $pdf -> Line(205.0, $soma + 0.0, 205.0, $soma + 82.0);

                $pdf -> Line(160.0, $soma + 10.0, 160.0, $soma + 42.0);
                $pdf -> setXY(160, $soma + 6);
                $pdf -> setFont('Helvetica', '', 8);
                $pdf -> Cell(40, 10, "Número da Parcela:", 0);
                $pdf -> setXY(160, $soma + 12);
                $pdf -> setFont('Helvetica', '', 14);
                $pdf -> Cell(45, 10, "$installment_number / $this->qty_installments", 0);
                $pdf -> setXY(167, $soma + 22);
                $pdf -> setFont('Helvetica', '', 20);
                $pdf -> Cell(40, 20, "$valor", 0);
                $pdf -> Line(5.0, $soma + 42.0, 205.0, $soma + 42.0);
                $pdf -> setXY(5, $soma + 43);
                $pdf -> setFont('Helvetica', '', 12);

                $pdf -> Cell(100, 10, "Em caso de atraso cobrar multa de $multa e juros de $juros ao dia.", 0);
                $pdf -> Line(160, $soma + 42, 160, $soma + 52.0);
                $pdf -> setXY(162, $soma + 39);
                $pdf -> setFont('Helvetica', '', 8);
                $pdf -> Cell(100, 10, "Vencimento:", 0);
                $pdf -> setXY(162, $soma + 44);
                $pdf -> setFont('Helvetica', '', 12);
                $pdf -> Cell(105, 10, "$data", 0);

                $pdf -> Line(5.0, $soma + 52.0, 205.0, $soma + 52.0);
                $pdf -> Line(5.0, $soma + 0.0, 5.0, $soma + 52.0);
                $pdf -> Line(205.0, $soma + 0.0, 205.0, $soma + 52.0);

                $pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 1, 'color' => array(0, 0, 0)));
                $pdf -> Line(0.0, $soma + 86.0, 210.0, $soma + 86.0);
                $is++;
                $id++;
                $soma = $soma + 90 ;
            }

            if ($i == 1) {
                $i = $i + 2;
            } else {
                $i = $i + 3;
            }
        }

        $pdf->Output('carne_' . $this->id . '.pdf', 'I');
    }
    /**
     * Cancela o Carnê caso o cliente queira
     *
     * @return null
     */
    public function cancel()
    {
        $this->status = 3;
        $this->update();

        $installments = AgInstallmentsInstallment::getInstallmentsFromInstallmentGroup($this->id);
        foreach ($installments as $installment) {
            $obj =new AgInstallmentsInstallment($installment['id_aginstallments_installment']);
            $obj->status = 3;
            $obj->update();
        }

        $order = new Order($this->id_order);
        $order->setCurrentState(6);
        $order->update();
    }
}
