
 <div class="modal fade" id="modalParcela" tabindex="-1" role="dialog" aria-labelledby="myModalLabelAppify">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-body">
            <form class="form-horizontal">
              <div class="row">
                <label class="control-label col-lg-3">{l s='Due date:' mod='aginstallments'}</label>
                <div class="col-lg-9">
                  <p class="form-control-static" id="vencParcela"></p>
                </div>
              </div>
              <div class="row">
                <label class="control-label col-lg-3">{l s='Installment Amount:' mod='aginstallments'}</label>
                <div class="col-lg-9">
                  <p class="form-control-static" id="valorParcela"></p>
                </div>
              </div>
              <div class="row">
                <label class="control-label col-lg-3">{l s='Interest:' mod='aginstallments'}</label>
                <div class="col-lg-9">
                  <p class="form-control-static" id="jurosParcela"></p>
                </div>
              </div>
              <div class="row">
                <label class="control-label col-lg-3">{l s='Fee:' mod='aginstallments'}</label>
                <div class="col-lg-9">
                  <p class="form-control-static" id="multaParcela"></p>
                </div>
              </div>
              <div class="row">
                <label class="control-label col-lg-3">{l s='Total:' mod='aginstallments'}</label>
                <div class="col-lg-9">
                  <p class="form-control-static" id="totalParcela"></p>
                </div>
              </div>
              <br>
              <div class='hidden'>
                <p>Dias de atraso:</p>
                <input id="diasParcela" type="text" class="form-control"></input> 
              </div>
              <button type="button" class="btn btn-default" id="btnAlteraDias">{l s='Change' mod='aginstallments'}</button>
            </div>
          </form>
          <div class="modal-footer">
            <a href = "" class="btn btn-default" id="payModalButton">{l s='Pay' mod='aginstallments'}</a>
            <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Close' mod='aginstallments'}</button>
          </div>
        </div>
      </div>
    </div>
