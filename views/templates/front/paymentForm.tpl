<form action="{$action}" id="payment-form" name="paymentForm">
    <h5>Selecione a Quantidade de Parcelas</h5>
    <select class="installment_select" name="select_installment">
        {foreach from=$qty_installments key=key item=installment}
            <option value="{intval($key) + 1}">{intval($key) + 1} vez(es) de: {$installment['installment_value']}</option>
        {/foreach}
    </select>
</form>
