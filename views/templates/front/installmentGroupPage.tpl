{extends file='page.tpl'}

{block name='page_title'}
  {l s='Installments List' mod='aginstallments'}
{/block}

{block name="page_content"}
	<p>{l s='These are the installments of the order %s in the amount of %s. The order was placed on %s' mod='aginstallments' sprintf=[$order->reference, Tools::displayPrice($installment_group->value), Tools::displayDate($order->date_add)]}.</p>
    {include file='module:aginstallments/views/templates/front/installmentsList.tpl'}
{/block}
