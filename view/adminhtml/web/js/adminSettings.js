require(['jquery', 'jquery/ui'], function($){ 
    var $m = $.noConflict();
    $m(document).ready(function() {
        
        $m("#lk_check1").change(function(){
            if($("#lk_check2").is(":checked") && $("#lk_check1").is(":checked")){
                $("#activate_plugin").removeAttr('disabled');
            }
        });
    
        $m("#lk_check2").change(function(){
            if($("#lk_check2").is(":checked") && $("#lk_check1").is(":checked")){
                $("#activate_plugin").removeAttr('disabled');
            }
        });

        $m(".navbar a").click(function() {
            $id = $m(this).parent().attr('id');
            setactive($id);
            $href = $m(this).data('method');
            voiddisplay($href);
        });
        $m(".btn-link").click(function() {
            $m(this).siblings('.show_info').slideToggle('slow');
            // if (!$m(this).next("div").is(':visible')) {
                //     $m(this).next("div").slideDown("slow");
                // }
             
        });

        //premium-setup-guide-instruction show logic is here
        $m(".btn-link-guide").click(function() {
        
            if (!$m(this).next("div").is(':visible')) {
                    //console.log("down");
                    $m(this).next("div").slideDown("slow");
                  
                }else{
                    //console.log("up");
                    $m(this).next("div").slideUp("slow");
                }
          
         });

       
        
        $m('#idpguide').on('change', function() {
            var selectedIdp =  jQuery(this).find('option:selected').val();
            $m('#idpsetuplink').css('display','inline');
            $m('#idpsetuplink').attr('href',selectedIdp);
        });
        $m("#mo_saml_add_shortcode").change(function(){
            $m("#mo_saml_add_shortcode_steps").slideToggle("slow");
        });
        $m('#error-cancel').click(function() {
            $error = "";
            $m(".error-msg").css("display", "none");
        });
        $m('#success-cancel').click(function() {
            $success = "";
            $m(".success-msg").css("display", "none");
        });
        $m('#cURL').click(function() {
            $m(".help_trouble").click();
            $m("#cURLfaq").click();
        });
        $m('#help_working_title1').click(function() {
            $m("#help_working_desc1").slideToggle("fast");
        });
        $m('#help_working_title2').click(function() {
            $m("#help_working_desc2").slideToggle("fast");
        });

        
        document.querySelectorAll(".copy-link").forEach((copyLinkParent) => {
         
            const inputField = copyLinkParent.querySelector(".copy-link-input");
            const copyButton = copyLinkParent.querySelector(".copy-link-button");
            const copyText = copyButton.querySelector(".copy-text");
            const text = inputField.value;
          
            inputField.addEventListener("focus", () => inputField.select());
          
            copyButton.addEventListener("mouseenter", () => {
              copyText.textContent = "Click to Copy";
              copyText.style.display = "block";
            });
          
            copyButton.addEventListener("mouseleave", () => {
              copyText.style.display = "none";
            });
          
            copyButton.addEventListener("click", () => {
              inputField.select();
             let inputElement=document.createElement('input');
             inputElement.setAttribute('value',text);
             document.body.appendChild(inputElement);
             inputElement.select();
             document.execCommand("copy");
             inputElement.parentNode.removeChild(inputElement);
              copyText.textContent = "Copied!";
              setTimeout(() => {
                copyText.style.display = "none";
                copyText.textContent = "Click to Copy";
              }, 500);
            });
          });


        
        
    });
});

function setactive($id) {
    $m(".navbar-tabs>li").removeClass("active");
    $id = '#' + $id;
    $m($id).addClass("active");
}

function voiddisplay($href) {
    $m(".page").css("display", "none");
    $m($href).css("display", "block");
}

function mosp_valid(f) {
    !(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(/[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
}

function showTestWindow() {
    var myWindow = window.open(testURL, "TEST SAML IDP", "scrollbars=1 width=800, height=600");	
}

function mosp_upgradeform(planType){
    jQuery('#requestOrigin').val(planType);
    jQuery('#mocf_loginform').submit();
}

function supportAction(){
    
}

//update---instead of checkbox using div

document.getElementById ("registered").addEventListener ("click", myFunction, false);

function myFunction() {
  var inputField = document.getElementById("myInput");
  var confirmPasswordElement = jQuery('#confirmPassword');
  var checkAllTopicCheckBoxes = document.getElementById('registered');
  var registerLoginButton = document.getElementById('registerLoginButton');
  var register_login = document.getElementById('register_login');
  const forget=document.getElementById("forget_pass");
  if (confirmPasswordElement.css('display') === 'none') {
    //login time
    confirmPasswordElement.css('display', 'block');
    registerLoginButton.value = "Register"; 
    checkAllTopicCheckBoxes.textContent = 'Already Registered ? Click here to Login';
    register_login.textContent = 'Register with miniOrange';
    confirmPasswordElement.prop('required', false); 
    forget.style.display = 'none';
    inputField.setAttribute("required", "required");
   // inputField.removeAttribute("required");
    
} else {
    //register time
    confirmPasswordElement.css('display', 'none');
    registerLoginButton.value = "Login"; 
    checkAllTopicCheckBoxes.textContent = 'Sign Up';
    register_login.textContent = 'Login with miniOrange';
    confirmPasswordElement.prop('required', true);
    forget.style.display = 'block'; 
    if (inputField.hasAttribute("required")) {
        inputField.removeAttribute("required");
    }
 //   inputField.removeAttribute("required");
   // inputField.setAttribute("required", "required");
  }
}

function upload_metadata()
{
    document.getElementById("upload_metadata_form").style.display = "block";
    jQuery('#spsettings').css('opacity', '0.6');
    jQuery('#support').css('opacity', '0.6');
}

function uploadFile()
    {
        jQuery('#upload_metadata_form').submit();
        document.getElementById("spsettings").style.display = "block";
        document.getElementById("upload_metadata_form").style.display = "none";
        jQuery('#spsettings').css('opacity', '100');
        jQuery('#support').css('opacity', '100');
    }

function cancel()
    {
        document.getElementById("spsettings").style.display = "block";
        document.getElementById("upload_metadata_form").style.display = "none";
        jQuery('#spsettings').css('opacity', '100');
        jQuery('#support').css('opacity', '100');
    }


    document.querySelectorAll(".copy-link").forEach((copyLinkParent) => {
        const inputField = copyLinkParent.querySelector(".copy-link-input");
        const copyButton = copyLinkParent.querySelector(".copy-link-button");
        const text = inputField.value;
      
        inputField.addEventListener("focus", () => inputField.select());
      
        copyButton.addEventListener("click", () => {
          inputField.select();
          navigator.clipboard.writeText(text);
      
          inputField.value = "Copied!";
          setTimeout(() => (inputField.value = text), 2000);
        });
      });
      