{extends file='page.tpl'}

{block name='page_title'}
    {hook h="displayWrapperTop"}

    {l s='Installments Group History' mod='aginstallments'}
{/block}
{block name="page_content"}
<div>
    <div class="list-group">
        <h3>{l s='Installments Group History' mod='aginstallments'}</h3>
        <br>
        <table class="table table-striped table-bordered table-labeled hidden-sm-down">
            <thead class="thead-default">
                <th scope="col">{l s='Order'     mod='aginstallments'}</th>
                <th scope="col">{l s='Order Date' mod='aginstallments'}</th>
                <th scope="col">{l s='Price' mod='aginstallments'}</th>
                <th scope="col">{l s='Total Paid' mod='aginstallments'}</th>
                <th scope="col">{l s='Fees Paid' mod='aginstallments'}</th>
                <th scope="col">{l s='Status' mod='aginstallments'}</th>
                <th scope="col">{l s='Details' mod='aginstallments'}</th>
            </thead>
            <tbody>
                {foreach from=$installments_groups key=key item=installments}
                    <tr>
                        <td>{$installments['reference_order']}</td>
                        <td>{Tools::displayDate($installments['date_add'])}</td>
                        <td>{$installments['value']}</td>
                        <td>{$installments['value_paid']}</td>
                        <td>{$installments['fee_paid']}</td>
                        {if $installments['status'] == 0}
                            <td><span class="label label-pill status0">{l s='Waiting Payment Confirm' mod='aginstallments'}</span> </td>
                        {else if $installments['status'] == '1'}
                            <td><span class="label label-pill status1">Vencido</span></td>
                        {else if $installments['status'] == '2'}
                            <td><span class="label label-pill status1">Cancelado</span></td>
                        {else}
                            <td><span class="label label-pill status2">{l s='Paid' mod='aginstallments'}</span></td>
                        {/if}
                        <td><a class="" href='{$installments["link"]}'>{l s='Details' mod='aginstallments'}</a></td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
        <div class="order-items hidden-md-up box">
            {foreach from=$installments_groups key=key item=installment}
                <div class="order-item row">
                    <div class="col-sm-5 desc">
                        <a class="link" href='{$installments["link"]}'>{l s='Details' mod='aginstallments'}</a>
                        <div class="name">Ref: {$installment['reference_order']}</div>
                        <div class="ref">{Tools::displayDate($installment['date_add'])}</div>
                    </div>
                    <div class="col-sm-7 qty">
                        <div class="row">
                        <div class="col-xs-4 text-sm-left text-xs-left">
                            Valor: {Tools::displayPrice($installment['value'])}
                        </div>
                        <div class="col-xs-4">
                            Taxas: {Tools::displayPrice($installment['fee_paid'])}
                        </div>
                        <div class="col-xs-4 text-xs-right statuses">
                            {if $installment['status'] == 0}
                            <span class="label label-pill status0">{l s='Waiting Payment Confirm' mod='aginstallments'}</span>
                            {else if $installment['status'] == '1'}
                            <span class="label label-pill status1">{l s='Expired' mod='aginstallments'}</span>
                            {else}
                            <span class="label label-pill status2">{l s='Paid' mod='aginstallments'}</span>
                            {/if}
                        </div>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
</div>

{/block}
