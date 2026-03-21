<?php
/**
 *
 */
class aginstallmentsListInstallmentsModuleFrontController extends ModuleFrontController
{

    public $auth = true;
    public function initContent()
    {
        parent::initContent();

        if (Tools::getValue('id_installment_group')!=null && Tools::getValue('generatePdf') == null) {
            $installment_group = new AgInstallmentsInstallmentGroup(Tools::getValue('id_installment_group'));
            $order = new Order($installment_group->id_order);
            $this->checkUser(Tools::getValue('id_installment_group'));
            $installments = self::getAllInstallmentsOfInstallmentsGroup(Tools::getValue('id_installment_group'));
            $installments = self::changeInstallmentStatus($installments);
            $i = 0;
            while ($i < count($installments)) {
                $installments[$i]['value'] = Tools::displayPrice($installments[$i]['value']);
                $installments[$i]['fee_paid'] = Tools::displayPrice($installments[$i]['fee_paid']);
                $installments[$i]['value_paid'] = Tools::displayPrice($installments[$i]['value_paid']);
                $i++;
            }

            $base_url = $this->context->link->getModuleLink($this->module->name, 'listInstallments');
            $link_print_pdf = $base_url  . '?generatePdf=1&id_installment_group=' . Tools::getValue('id_installment_group');
            $this->context->controller->addCss(_PS_MODULE_DIR_ . 'aginstallments/views/css/front/listInstallments.css');
            $this->context->smarty->assign([
                'installment_group' => $installment_group,
                'installments' => $installments,
                'linkPdf' => $link_print_pdf,
                'order' => $order
                //'breadcrumb' => $this->getBreadcrumbLinks('installments_group')
            ]);
            $this->setTemplate('module:aginstallments/views/templates/front/installmentGroupPage.tpl');
        } else if (Tools::getValue('generatePdf') != null) {
            $this->generatePdf();
            exit();
        } else {
            $id_customer = $this->context->customer->id;
            $ids_installments_group = $this->getAllIdInstallmentsByIdCustomer($id_customer);
            $installments_groups = $this->getAllInstallmentsGroupByIdInstallmentsGroup($ids_installments_group);
            $this->context->controller->addCss(_PS_MODULE_DIR_ . 'aginstallments/views/css/front/listInstallments.css');
            $this->context->smarty->assign([
                'installments_groups' => $installments_groups,
            ]);

            $this->setTemplate('module:aginstallments/views/templates/front/listInstallments.tpl');
        }
    }

    public function getAllInstallmentsGroupByIdInstallmentsGroup($ids_installments_group)
    {
        $all_installments_group = [];
        for ($i=0; $i < count($ids_installments_group) ; $i++) {
            $query = new DbQuery;
            $query->from('aginstallments_installment_group');
            $query->where('id_aginstallments_installment_group ="'. $ids_installments_group[$i] .'" ');
            $installments_group = Db::getInstance()->getRow($query);
            //getting a other data to installments group
            $qty_installments_payed = [];

            $installments_group['value'] = Tools::displayPrice($installments_group['value']);
            $installments_group['value_paid'] = Tools::displayPrice($installments_group['value_paid']);
            $installments_group['fee_paid'] = Tools::displayPrice($installments_group['fee_paid']);

            $installments_group['link'] = $this->getInstallmentInstallmentsLink($installments_group['id_aginstallments_installment_group']);
            $installments_group['paid_installments'] = self::getNumberOfPaidInstallments($installments_group['id_aginstallments_installment_group']);
            $installments_group['reference_order'] = self::getReferenceOrder($installments_group['id_order']);

            $all_installments_group[] = $installments_group;
        }
        return $all_installments_group;
    }

    private function getAllIdInstallmentsByIdCustomer($id_customer) {
        $query = new DbQuery;
        $query->from('orders');
        $query->where('id_customer ="'. $id_customer .'" and payment="Pagamento via Carnês"');
        $ids_order = Db::getInstance()->executeS($query);
        $i = 0;
        $ids_installments_group = [];
        while ($i < count($ids_order)) {
            $query = new DbQuery;
            $query->select('id_aginstallments_installment_group');
            $query->from('aginstallments_installment_group');
            $query->where('id_order ="'. $ids_order[$i]["id_order"] .'"');
            $id_installments_group = Db::getInstance()->getValue($query);
            if ($id_installments_group != false) {
                array_push($ids_installments_group, $id_installments_group);
            } else {
                $i++;
                continue;
            }
            $i++;
        }

        return $ids_installments_group;
    }

    private static function getReferenceOrder($id_order)
    {
        $order = new Order($id_order);
        return $order->reference;
    }

    private static function getNumberOfPaidInstallments($id_installment_group)
    {
        $installments = AgInstallmentsInstallment::getInstallmentsFromInstallmentGroup($id_installment_group);

        $payed = 0;
        $i = 0;
        while($i < count($installments)) {
            $status = $installments[$i]['status'];
            if ($status == 2 || $status == '2') {
                $payed = $payed + 1;
            }
            $i++;
        }
        return $payed;
    }

    private static function addFieldOnInstallment($installment_group, array $field)
    {
        $installment_group = array_merge($installment_group, $field);
        return $installment_group;
    }

    private function getInstallmentInstallmentsLink($id_installments_group)
    {
        $link = $this->context->link->getModuleLink($this->module->name, 'listInstallments');
        $link = $link . '?id_installment_group=' . $id_installments_group;

        return $link;
    }

    private static function getAllInstallmentsOfInstallmentsGroup($id_installments_group)
    {
        $query = new DbQuery;
        $query->from('aginstallments_installment');
        $query->where('id_aginstallments_installment_group ="'. $id_installments_group .'" ');
        return Db::getInstance()->executeS($query);
    }

    private static function changeInstallmentStatus($installment_group)
    {
        for ($i=0; $i < count($installment_group) ; $i++) {
            $date_limit = $installment_group[$i]['date_limit'];
            if (strtotime($date_limit) - strtotime("now") < 0 && $installment_group[$i]['status'] == 0) {
                $installment_group[$i]['status'] = 1;
            } else {
                $i++;
                continue;
            }
        }
        return $installment_group;
    }

    private function generatePdf()
    {
        $id_carne = Tools::getValue('id_installment_group');
        $obj = new AgInstallmentsInstallmentGroup($id_carne);
        $pdfGen = $obj->generatePdf($this->module->getConfigurationFromDb(), $this->module);
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();


        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();

        $link = $this->context->link->getModuleLink($this->module->name, 'listInstallments');
        if (Tools::getValue('id_installment_group') == null) {
            return $breadcrumb;
        } else {
            $page = 'Seus Carnês';
            $breadcrumb['links'][] = [
                'title' => $this->trans($page, array(), 'Shop.Theme.Global'),
                'url' => $link . '?id_customer=' .$this->context->customer->id,
            ];
            $breadcrumb['count'] = count($breadcrumb['links']);
        }
        return $breadcrumb;
    }

    public function checkUser($id_carne)
    {
        $installment_group = new AgInstallmentsInstallmentGroup($id_carne);
        $id_order = $installment_group->id_order;
        $order = new Order($id_order);
        $idUserOrderCreator = $order->id_customer;
        $userLogged = $this->context->customer->id;
        if ($idUserOrderCreator != $userLogged) {
            return Tools::redirect($this->context->link->getModuleLink($this->module->name, 'listInstallments'));
        } else {
            return true;
        }
    }

}
