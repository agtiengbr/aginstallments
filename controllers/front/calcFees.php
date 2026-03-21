<?php
class AgInstallmentsCalcFeesModuleFrontController extends ModuleFrontController
{
    /**
     * Controlador que deve ser executado uma vez ao dia para calcular o total de juros e multa para cada parcela atrasada
     *
     * @return null
     */ 
    public function initContent()
    {
        /** @var AgClienteWorker */
        global $agti_worker;
        $agti_worker = new AgClienteWorker(Tools::getValue('id_agworker'));

        set_time_limit(0);
        ignore_user_abort(true);

        Configuration::updateValue('aginstallments_calc_fees_next_time', time() + 24 * 60 * 60);
        
        AgInstallmentsInstallment::markDelayedInstallments();
        AgInstallmentsInstallment::markDelayedInstallmentsGroup();

        $installments = AgInstallmentsInstallment::getAllInstallments();
        $totalInstallments = count($installments);
        $i = 0;            
        while ($i < $totalInstallments) {
            $agti_worker->save();
            $idParcela = $installments[$i]['id_aginstallments_installment'];
            $installmentsObj = new AgInstallmentsInstallment($idParcela);
            $installmentsObj->calcInterestAndFee();  
            $i++;
        }
        
        exit();
    }
}