jQuery(document).ready(function(){

                showHidePaymentoptions(jQuery("#woocommerce_sbpo_sbpo_sandboxmode"));

                jQuery(document).on("click","#woocommerce_sbpo_sbpo_sandboxmode" ,function(){
                    showHidePaymentoptions(jQuery(this))
                });

                function showHidePaymentoptions(element){
                    var checked = jQuery(element).is(":checked")
                   if(checked){
                        jQuery("#woocommerce_sbpo_sbpo_merchant_id,#woocommerce_sbpo_sbpo_api_url,#woocommerce_sbpo_sbpo_api_key").parent().parent().parent().hide();
                        jQuery("#woocommerce_sbpo_sbpo_sn_merchant_id,#woocommerce_sbpo_sbpo_sn_api_url,#woocommerce_sbpo_sbpo_sn_api_key").parent().parent().parent().show();
                        
                   } else{
                    jQuery("#woocommerce_sbpo_sbpo_sn_merchant_id,#woocommerce_sbpo_sbpo_sn_api_url,#woocommerce_sbpo_sbpo_sn_api_key").parent().parent().parent().hide();
                        
                         jQuery("#woocommerce_sbpo_sbpo_merchant_id,#woocommerce_sbpo_sbpo_api_url,#woocommerce_sbpo_sbpo_api_key").parent().parent().parent().show();
                          
                    }
                }
                    
            });   