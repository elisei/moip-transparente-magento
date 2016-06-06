(function($) {
      
MoipPagamentosCc = function(){
              jQuery('#moip_cc_type li').addClass('active');
              jQuery('.save-card').prop( "checked", true );
              jQuery('.save-card').val(1);
 

              jQuery('#onestep_form :input').blur(function() {
                if(jQuery(this).attr('id') != "billing:day" && jQuery(this).attr('id') != "billing:month"){
                    Validation.validate(jQuery(this).attr('id'));
                  }
              });
              jQuery('#moip_cc_number').keyup(function(){
                        if(!jQuery("#moip_cc_number").val().length){
                          jQuery('#moip_cc_type li').addClass('active');
                        } 
                        
                      }
              );
              jQuery('#moip_cc_number').focusout(function() {

                  jQuery('#moip_cc_type li').removeClass('active');

                  var cc = new Moip.CreditCard({
                    number  : jQuery("#moip_cc_number").val(),
                  });
                  var brand_moip = cc.cardType();
                  if(brand_moip){
                    switch(cc.cardType()) {
                      case 'VISA':
                        jQuery('#moip_cc_type .VI').addClass('active');
                        jQuery("input[id=moip_cc_type]").val('VI');
                       break;
                      case 'MASTERCARD':
                        jQuery('#moip_cc_type .MC').addClass('active');
                        jQuery("input[id=moip_cc_type]").val('MC');
                       break;
                      case 'AMEX':
                        jQuery('#moip_cc_type .AE').addClass('active');
                        jQuery("input[id=moip_cc_type]").val('AE');
                       break;
                      case 'DINERS':
                        jQuery('#moip_cc_type .DI').addClass('active');
                        jQuery("input[id=moip_cc_type]").val('DC');
                       break;
                      case 'ELO':
                        jQuery('#moip_cc_type .EO').addClass('active');
                        jQuery("input[id=moip_cc_type]").val('EO');
                       break;
                      case 'HIPERCARD':
                        jQuery('#moip_cc_type .HI').addClass('active');
                        jQuery("input[id=moip_cc_type]").val('HP');
                       break;
                      case 'HIPER':
                        jQuery('#moip_cc_type .HP').addClass('active');
                        jQuery("input[id=moip_cc_type]").val('HI');
                       break;
                   };
                  } else {
                   // alert("Desculpe, não conseguimos validar o número do seu cartão, possivelmente ele não é válido ou é de uma bandeira que não trabalhamos.");
                    jQuery("#moip_cc_number").removeClass("validation-passed");
                    jQuery("#moip_cc_number").addClass("validation-failed");
                  }
                  
                   
            });
            jQuery("#moip_cc_number").on('change', function() {
                      var cc = new Moip.CreditCard({
                        number  : jQuery("#moip_cc_number").val(),
                        cvc     : jQuery("#moip_cc_cid").val(),
                        expMonth: jQuery("#credito_expiracao_mes").val(),
                        expYear : jQuery("#credito_expiracao_ano").val(),
                        pubKey  : jQuery("#id-chave-publica").val()
                      });
                      if(cc.isValid()){
                          jQuery("#encrypted_value").val(cc.hash());
                      }
                      else{
                          jQuery("#encrypted_value").val('');
                      }
            });
            jQuery("#moip_cc_cid").on('change', function() {
                      var cc = new Moip.CreditCard({
                        number  : jQuery("#moip_cc_number").val(),
                        cvc     : jQuery("#moip_cc_cid").val(),
                        expMonth: jQuery("#credito_expiracao_mes").val(),
                        expYear : jQuery("#credito_expiracao_ano").val(),
                        pubKey  : jQuery("#id-chave-publica").val()
                      });
                      if(cc.isValid()){
                          jQuery("#encrypted_value").val(cc.hash());
                      }
                      else{
                          jQuery("#encrypted_value").val('');
                      }
            });

            jQuery("#credito_expiracao_mes").on('change', function() {
                      var cc = new Moip.CreditCard({
                        number  : jQuery("#moip_cc_number").val(),
                        cvc     : jQuery("#moip_cc_cid").val(),
                        expMonth: jQuery("#credito_expiracao_mes").val(),
                        expYear : jQuery("#credito_expiracao_ano").val(),
                        pubKey  : jQuery("#id-chave-publica").val()
                      });
                      if(cc.isValid()){
                          jQuery("#encrypted_value").val(cc.hash());
                      }
                      else{
                          jQuery("#encrypted_value").val('');
                      }
            });
            jQuery("#credito_expiracao_ano").on('change', function() {          
                      var cc = new Moip.CreditCard({
                        number  : jQuery("#moip_cc_number").val(),
                        cvc     : jQuery("#moip_cc_cid").val(),
                        expMonth: jQuery("#credito_expiracao_mes").val(),
                        expYear : jQuery("#credito_expiracao_ano").val(),
                        pubKey  : jQuery("#id-chave-publica").val()
                      });
                      if(cc.isValid()){
                          jQuery("#encrypted_value").val(cc.hash());
                      }
                      else{
                          jQuery("#encrypted_value").val('');
                      }
            });

            jQuery('.card-port').fadeOut('slow');

            jQuery('.titular-card').change(function(){
              if(this.checked)
                  jQuery('.card-port').fadeOut('slow');
              else
                  jQuery('.card-port').fadeIn('slow');
            });

            jQuery('.save-card').change(function(){
              if(this.checked)
                  jQuery('.save-card').val(1);
              else
                  jQuery('.save-card').val('');
            });

            jQuery('.new-card').change(function(){
                    if(this.checked){
                        jQuery('.payment-cofre').fadeOut('slow');
                        jQuery('.new-card').val(1);
                        jQuery('.payment-card').fadeIn('slow');
                    }
                      
                    else{
                          jQuery('.new-card').val(0);
                          jQuery('.payment-card').fadeOut('slow');
                          jQuery('.payment-cofre').fadeIn('slow');
                    }
                }
            );

            jQuery('#personal-card-info-control').change(function(){
              if(this.checked){
                 jQuery('#personal-card-info-control').val(1);
                 jQuery(".personal-card-info").addClass('no-display');
                 jQuery(".personal-card-info").fadeOut();
              }
              else
              {
                 jQuery('#personal-card-info-control').val(0);
                 jQuery(".personal-card-info").removeClass('no-display');
                 jQuery(".personal-card-info").fadeIn();
                 

              }
            });
}

SetCofre = function(){
  jQuery('.payment-card').fadeOut('fast');
  jQuery('.save-card').prop( "checked", false );
  jQuery('.save-card').val(0);
}

MoipPagamentosRecurring = function(){
              jQuery('#moip_ccrecurring_type li').addClass('active');
             
           
             
              jQuery('#moip_ccrecurring_number').keyup(function(){
                        if(!jQuery("#moip_ccrecurring_number").val().length){
                          jQuery('#moip_ccrecurring_type li').addClass('active');
                        } 
                        
                      }
              );
              jQuery('#moip_ccrecurring_number').focusout(function() {

                  jQuery('#moip_ccrecurring_type li').removeClass('active');

                  var ccrecurring = new Moip.CreditCard({
                    number  : jQuery("#moip_ccrecurring_number").val(),
                  });
                  var brand_moip = ccrecurring.cardType();
                  if(brand_moip){
                    switch(ccrecurring.cardType()) {
                      case 'VISA':
                        jQuery('#moip_ccrecurring_type .VI').addClass('active');
                        jQuery("input[id=moip_ccrecurring_type]").val('VI');
                       break;
                      case 'MASTERCARD':
                        jQuery('#moip_ccrecurring_type .MC').addClass('active');
                        jQuery("input[id=moip_ccrecurring_type]").val('MC');
                       break;
                      case 'AMEX':
                        jQuery('#moip_ccrecurring_type .AE').addClass('active');
                        jQuery("input[id=moip_ccrecurring_type]").val('AE');
                       break;
                      case 'DINERS':
                        jQuery('#moip_ccrecurring_type .DI').addClass('active');
                        jQuery("input[id=moip_ccrecurring_type]").val('DC');
                       break;
                      case 'ELO':
                        jQuery('#moip_ccrecurring_type .EO').addClass('active');
                        jQuery("input[id=moip_ccrecurring_type]").val('EO');
                       break;
                      case 'HIPERCARD':
                        jQuery('#moip_ccrecurring_type .HI').addClass('active');
                        jQuery("input[id=moip_ccrecurring_type]").val('HP');
                        alert('Ainda nao estamos trabalhando com o HIPERCARD, por favor escolha outro cartão');
                       break;
                      case 'HIPER':
                        jQuery('#moip_ccrecurring_type .HP').addClass('active');
                        jQuery("input[id=moip_ccrecurring_type]").val('HI');
                        alert('Ainda nao estamos trabalhando com o HIPER, por favor escolha outro cartão');
                       break;
                   };
                  } else {
                    alert("Desculpe, não conseguimos validar o número do seu cartão, possivelmente ele não é válido ou é de uma bandeira que não trabalhamos.");
                    jQuery("#moip_ccrecurring_number").removeClass("validation-passed");
                    jQuery("#moip_ccrecurring_number").addClass("validation-failed");
                  }
                  
                   
            });
            jQuery("#moip_ccrecurring_number").on('change', function() {
                      var ccrecurring = new Moip.CreditCard({
                        number  : jQuery("#moip_ccrecurring_number").val(),
                        cvc     : jQuery("#moip_ccrecurring_cid").val(),
                        expMonth: jQuery("#credito_expiracao_mes").val(),
                        expYear : jQuery("#credito_expiracao_ano").val(),
                        pubKey  : jQuery("#id-chave-publica").val()
                      });
                      if(ccrecurring.isValid()){
                          jQuery("#encrypted_value").val(ccrecurring.hash());
                      }
                      else{
                          jQuery("#encrypted_value").val('');
                      }
            });
            jQuery("#moip_ccrecurring_cid").on('change', function() {
                      var ccrecurring = new Moip.CreditCard({
                        number  : jQuery("#moip_ccrecurring_number").val(),
                        cvc     : jQuery("#moip_ccrecurring_cid").val(),
                        expMonth: jQuery("#credito_expiracao_mes").val(),
                        expYear : jQuery("#credito_expiracao_ano").val(),
                        pubKey  : jQuery("#id-chave-publica").val()
                      });
                      if(ccrecurring.isValid()){
                          jQuery("#encrypted_value").val(ccrecurring.hash());
                      }
                      else{
                          jQuery("#encrypted_value").val('');
                      }
            });

            jQuery("#credito_expiracao_mes").on('change', function() {
                      var ccrecurring = new Moip.CreditCard({
                        number  : jQuery("#moip_ccrecurring_number").val(),
                        cvc     : jQuery("#moip_ccrecurring_cid").val(),
                        expMonth: jQuery("#credito_expiracao_mes").val(),
                        expYear : jQuery("#credito_expiracao_ano").val(),
                        pubKey  : jQuery("#id-chave-publica").val()
                      });
                      if(ccrecurring.isValid()){
                          jQuery("#encrypted_value").val(ccrecurring.hash());
                      }
                      else{
                          jQuery("#encrypted_value").val('');
                      }
            });
            jQuery("#credito_expiracao_ano").on('change', function() {          
                      var ccrecurring = new Moip.CreditCard({
                        number  : jQuery("#moip_ccrecurring_number").val(),
                        cvc     : jQuery("#moip_ccrecurring_cid").val(),
                        expMonth: jQuery("#credito_expiracao_mes").val(),
                        expYear : jQuery("#credito_expiracao_ano").val(),
                        pubKey  : jQuery("#id-chave-publica").val()
                      });
                      if(ccrecurring.isValid()){
                          jQuery("#encrypted_value").val(ccrecurring.hash());
                      }
                      else{
                          jQuery("#encrypted_value").val('');
                      }
            });

            jQuery('.card-port').fadeOut('slow');

            jQuery('.titular-card').change(function(){
              if(this.checked)
                  jQuery('.card-port').fadeOut('slow');
              else
                  jQuery('.card-port').fadeIn('slow');
            });

            jQuery('.save-card').change(function(){
              if(this.checked)
                  jQuery('.save-card').val(1);
              else
                  jQuery('.save-card').val('');
            });

            jQuery('.new-card').change(function(){
                    if(this.checked){
                        jQuery('.payment-cofre').fadeOut('slow');
                        jQuery('.new-card').val(1);
                        jQuery('.payment-card').fadeIn('slow');
                    }
                      
                    else{
                          jQuery('.new-card').val(0);
                          jQuery('.payment-card').fadeOut('slow');
                          jQuery('.payment-cofre').fadeIn('slow');
                    }
                }
            );
}
})(jQuery);