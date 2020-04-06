(function($) {

	var onclick_proccess_form = new VarienForm('onclick_proccess');

     onclick_proccess_form.submit = function (button, url) {
          if (this.validator.validate()) {
              var form = this.form;
             
              var data = jQuery('#onclick_proccess').serialize();
             
              try {
                jQuery(".form-post-loading").hide();
                jQuery(".progress").show();
                  jQuery.ajax({
                        url: form.action,
                        dataType: 'json',
                        type : 'post',
                        data: data,
                        success: function(data){
                           
                            window.location.href = data.url_redirect;
                          
                        }
                  });
              } catch (e) {
              }
          }
      }.bind(onclick_proccess_form);
      
   

})(jQuery);