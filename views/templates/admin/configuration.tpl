<form name='aginstallments' class="form-horizontal" method="post"  method="post" enctype='multipart/form-data'>
    <ps-tabs position="top">
        <ps-tab label="Configurações" id="tabConfigurations" icon="icon-cogs" fa="cogs" active="true">
            <ps-panel header="Configurações">
                <ps-input-text name="aginstallments_max_installments" label="Máximo de Parcelas" value="{$aginstallments_max_installments}"></ps-input-text>
                <ps-input-text name="aginstallments_min_value_installment" label="Valor Mínimo da Parcela" prefix="R$" value="{$aginstallments_min_value_installment}"></ps-input-text>
                <ps-input-text name="aginstallments_interest_rate" label="Taxa de Juros" suffix="%" value="{$aginstallments_interest_rate}"></ps-input-text>
                <ps-input-text help="O máximo permitido pela legislação atual é de 1% a.m." name="aginstallments_interest_rate_late_payment" label="Taxa de Juros por Atraso" suffix="% a.m." value="{$aginstallments_interest_rate_late_payment}"></ps-input-text>
                <ps-input-text help="O máximo permitido pela legislação atual é de 2%" name="aginstallments_fee" label="Multa por Atraso" suffix="%" value="{$aginstallments_fee}"></ps-input-text>

                <ps-switch help="Se SIM a primeira prestação deverá ser paga no ato da compra" name="aginstallments_autopay_first_install" label="Pagamento com Entrada" yes="Sim" no="Não" active="{if $aginstallments_autopay_first_install}true{else}false{/if}"></ps-switch>

                <div class='form-group'>
                	<label class="control-label col-lg-3">{l s='Image' mod='aginstallments'}</label>
                    <div class="col-lg-9">
                        <img id="aginstallments_logo" src="{$aginstallments_logo}" />
                    	<input type="file" name="aginstallments_logo"></ps-panel>
                    </div>
                </div>

                <ps-panel-footer>
                    <ps-panel-footer-submit direction="left" title="Cancelar" icon='process-icon-cancel'></ps-panel-footer-submit>
                    <ps-panel-footer-submit direction="right" title="Salvar" icon='process-icon-save' name="aginstallments-save"></ps-panel-footer-submit>
                </ps-panel-footer>
            </ps-panel>
        </ps-tab>

        <ps-tab label="Mapeamentos" id="tabMappings" icon="icon-arrows-h" fa="arrows-h">
            <ps-panel icon="icon-user" fa="user" header="Campos">
                <ps-select name="aginstallments_cpf" label='CPF'>
                    {foreach from=$module->getCpfMapping()->getColumnsFromTable() key=key item=column}
                        <option value="{$key}" {if $module->getCpfMapping()->getMappedField() == $key}selected="selected"{/if}>{$column}</option>
                    {/foreach}
                </ps-select>

                <ps-select name="aginstallments_cnpj" label='CNPJ'>
                    {foreach from=$module->getCnpjMapping()->getColumnsFromTable() item=column key=key}
                        <option value="{$key}" {if $module->getCnpjMapping()->getMappedField() == $key}selected="selected"{/if}>{$column}</option>
                    {/foreach}
                </ps-select>

                <ps-select name="aginstallments_social_name" label='Razão Social'>
                    {foreach from=$module->getSocialNameMapping()->getColumnsFromTable() item=column key=key}
                        <option value="{$key}" {if $module->getSocialNameMapping()->getMappedField() == $key}selected="selected"{/if}>{$column}</option>
                    {/foreach}
                </ps-select>


                <ps-select name="aginstallments_address_number" label='Número do Endereço'>
                    {foreach from=$module->getAddressNumberMapping()->getColumnsFromTable() item=column key=key}
                        <option value="{$key}" {if $module->getAddressNumberMapping()->getMappedField() == $key}selected="selected"{/if}>{$column}</option>
                    {/foreach}
                </ps-select>


                <ps-panel-footer>
                    <ps-panel-footer-submit direction="left" title="Cancelar" icon='process-icon-cancel'></ps-panel-footer-submit>
                    <ps-panel-footer-submit direction="right" title="Salvar" icon='process-icon-save' name="agyapay-save"></ps-panel-footer-submit>
                </ps-panel-footer>
            </ps-panel>
        </ps-tab>
    </ps-tabs>
</form>
