<?php
/**
 *
 */
class aginstallmentsmakePdfModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();
        $id_carne = Tools::getValue('id_aginstallments_installment_group');
        $obj = new AgInstallmentsInstallmentGroup($id_carne);
        $order = new Order($obj->id_order);
        if ($order->id_customer == $this->context->customer->id) {
            $obj->generatePdf($this->module->getConfigurationFromDb(), $this->module);
        } else {
            header('403 Forbidden');
            exit();
        }

        exit();
    }
}
