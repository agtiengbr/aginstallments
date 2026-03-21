<?php
/**
 *
 */
class aginstallmentsmakePaymentModuleFrontController extends ModuleFrontController
{

    function postProcess()
    {
        $qty_installments = Tools::getValue('select_installment');
        $id_cart = intval($this->context->cart->id);

        $id_order = $this->module->validateOrder(intval($this->context->cart->id), intval('1'), floatval($this->context->cart->getOrderTotal()), 'Pagamento via Carnês');
        $ps_order = new Order(Order::getOrderByCartId($id_cart));
        $installment = AgInstallmentsInstallmentGroup::findByOrder($ps_order);
        $this->context->smarty->assign(array(
            'installment' => $installment
        ));

        $token = Tools::getToken();

        return Tools::redirect('index.php?controller=order-confirmation&id_cart='.$id_cart.'&id_module='.$this->module->id.'&id_order='.$ps_order->id.'&key='.$this->context->customer->secure_key.'&installmentId='.$installment->id.'&token='.$token);
    }
}
