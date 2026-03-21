<div class="box box-button-link">
    <div class="list-group">
        <h3>{l s='Installments List' mod='aginstallments'}</h3>
        <table class="table table-striped table-bordered table-labeled hidden-md-down">
            <thead class="thead-default">
                <th scope="col">{l s='Reference' mod='aginstallments'}</th>
                <th scope="col">{l s='Date Limit' mod='aginstallments'}</th>
                <th scope="col">{l s='Value' mod='aginstallments'}</th>
                <th scope="col">{l s='Paid Value' mod='aginstallments'}</th>
                <th scope="col">{l s='Fee Paid' mod='aginstallments'}</th>
                <th scope="col">{l s='Status' mod='aginstallments'}</th>
            </thead>
            <tbody>
                {foreach from=$installments key=key item=installment}
                <tr class="list-items">
                    <td>{$installment['reference']}</td>
                    <td>{Tools::displayDate($installment['date_limit'])}</td>
                    <td>{$installment['value']}</td>
                    <td>{$installment['value_paid']}</td>
                    <td>{$installment['fee_paid']}</td>
                    {if $installment['status'] == 0}
                    <td><span class="label label-pill status0">{l s='Waiting Payment Confirm' mod='aginstallments'}</span> </td>
                    {else if $installment['status'] == '1'}
                    <td><span class="label label-pill status1">{l s='Expired' mod='aginstallments'}</span></td>
                    {else}
                    <td><span class="label label-pill status2">{l s='Paid' mod='aginstallments'}</span></td>
                    {/if}
                </tr>
                {/foreach}
            </tbody>
        </table>

        <div class="order-items hidden-lg-up box">
            {foreach from=$installments key=key item=installment}
                <div class="order-item row">
                    <div class="col-sm-4 desc">
                        <div class="name">Ref: {$installment['reference']}</div>
                        <div class="ref">{Tools::displayDate($installment['date_limit'])}</div>
                    </div>
                    <div class="col-sm-8 qty">
                        <div class="row">
                        <div class="col-sm-3 text-sm-left text-sm-left">
                            Valor: {Tools::displayPrice($installment['value'])}
                        </div>
                        <div class="col-sm-2">
                            Taxas: {Tools::displayPrice($installment['fee_paid'])}
                        </div>
                        <div class="col-sm-7 text-sm-right statuses">
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

        <div class='text-sm-center'>
            <a href="{$linkPdf}" target="_blank" class="btn btn-primary">{l s='Print' mod='aginstallments'}</a>
        </div>
    </div>
</div>
