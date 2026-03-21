<?php
/**
 *
 */
class aginstallmentsmakepdfModuleFrontController extends ModuleFrontController
{

    function __construct($argument)
    {

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
}
