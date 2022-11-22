<?php


function mo_saml_show_customer_details()
{
?>
    <div class="row container-fluid" action="" id="account-info-form">
        <div class="col-md-8 mt-4 ms-5">
            <div class="p-4 shadow-cstm bg-white rounded">
                <h4 class="form-head"><?php esc_html_e('Thank you for registering with miniOrange', 'miniorange-saml-20-single-sign-on'); ?></h4>

                <table class="w-100 mt-4">
                    <tr style="border: 0.5px solid #fff;background: #e9f0ff;">
                        <td style="width:45%; padding: 10px;"><?php esc_html_e('miniOrange Account Email', 'miniorange-saml-20-single-sign-on'); ?></td>
                        <td style="width:55%; padding: 10px;"><?php esc_html_e(get_option('mo_saml_admin_email'), 'miniorange-saml-20-single-sign-on'); ?></td>
                    </tr>
                    <tr style="border: 0.5px solid #fff;background: #e9f0ff;">
                        <td style="width:45%; padding: 10px;"><?php esc_html_e('Customer ID', 'miniorange-saml-20-single-sign-on'); ?></td>
                        <td style="width:55%; padding: 10px;"><?php esc_html_e(get_option('mo_saml_admin_customer_key'), 'miniorange-saml-20-single-sign-on'); ?></td>
                    </tr>
                </table>
                <br /><br />

                <table>
                    <tr>
                        <td>
                            <form name="f1" method="post" action="" id="mo_saml_goto_login_form">
                                <?php wp_nonce_field("change_miniorange"); ?>
                                <input type="hidden" value="change_miniorange" name="option" />
                                <input type="submit" value="<?php esc_attr_e('Switch Account', 'miniorange-saml-20-single-sign-on'); ?>" class="mo-saml-bs-btn btn-cstm" />
                            </form>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(array('tab' => 'licensing'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"><input type="button" class="mo-saml-bs-btn btn-cstm" value="<?php esc_attr_e('Check Licensing Plans', 'miniorange-saml-20-single-sign-on'); ?>" /></a>
                        </td>
                    </tr>
                </table>

                <br />
            </div>
        </div>
        <?php mo_saml_display_support_form(); ?>
    </div>
<?php
}

function mo_saml_show_new_registration_page_saml()
{

?>
    <div class="row m-4" id="acc-tab-form">
        <div class="p-4 bg-white rounded">
            <h4 class="form-head">Register with miniOrange</h4>
            <div class="row justify-content-center">
                <div class="col-md-6 mt-5">
                    <h5 class="text-center mo-saml-why-reg-txt">Why should I register?</h5>
                    <h5 class="text-center mo-saml-why-login-txt">Why should I login?</h5>
                    <p class="mt-3 mo-saml-why-reg mo-saml-why-reg-txt"> You should register so that in case you need help, we can help you with step by step instructions. We support all known IdPs - ADFS, Okta, Salesforce, Shibboleth, SimpleSAMLphp, OpenAM, Centrify, Ping, RSA, IBM, Oracle, OneLogin, Bitium, WSO2 etc. <b>You will also need a miniOrange account to upgrade to the premium version of the plugins.</b> We do not store any information except the email that you will use to register with us.</p>
                    <p class="mt-3 mo-saml-why-reg mo-saml-why-login-txt">You should login so that you can easily reach out to us in case you face any issues while setting up the SSO with your IDP. <b>You will also need a miniOrange account to upgrade to the premium version of the plugins.</b> We do not store any information except the email that you will use to register with us.</p>
                    <div class="text-center">
                        <img src="<?php echo esc_url(Utilities::mo_saml_get_plugin_dir_url() . 'images/mo-saml-registration-form-bg.webp'); ?>" width="46%" alt="wordpress saml registration form">
                    </div>
                </div>
                <div class="col-md-5 mt-5 rounded reg-form">
                    <form name="f" method="post" action="">
                        <input type="hidden" name="option" value="mo_saml_register_customer" />
                        <?php wp_nonce_field("mo_saml_register_customer"); ?>

                        <div class="row align-items-center justify-content-center mt-4 mo-saml-reg-field">
                            <div class="col-md-6">
                                <h6 class="text-secondary">Email <span class="text-danger">* </span>:</h6>
                            </div>
                            <div class="col-md-6 ps-0">
                                <input type="text" name="registerEmail" placeholder="person@example.com" required value="" class="w-100 mo-saml-reg-text-field">
                            </div>
                        </div>
                        <div class="row align-items-center justify-content-center mt-4 mo-saml-reg-field">
                            <div class="col-md-6">
                                <h6 class="text-secondary">Password <span class="text-danger">* </span>:</h6>
                            </div>
                            <div class="col-md-6 ps-0">
                                <input class="w-100 mo-saml-reg-text-field" required type="password" name="password" placeholder="Password (Min. length 6)" minlength="6" pattern="^[(\w)*(!@#$.%^&amp;*-_)*]+$" title="Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&amp;*) should be present.">
                            </div>
                        </div>
                        <div class="row align-items-center justify-content-center mt-4 mo-saml-reg-field">
                            <div class="col-md-6">
                                <h6 class="text-secondary">Confirm Password <span class="text-danger">* </span>:</h6>
                            </div>
                            <div class="col-md-6 ps-0">
                                <input class="w-100 mo-saml-reg-text-field" required type="password" name="confirmPassword" placeholder="Confirm your password" minlength="6" pattern="^[(\w)*(!@#$.%^&amp;*-_)*]+$">
                            </div>
                        </div>

                        <div class="row align-items-center justify-content-center mt-4 mo-saml-already-reg-field">
                            <div class="col-md-5">
                                <h6 class="text-secondary">Email <span class="text-danger">* </span>:</h6>
                            </div>
                            <div class="col-md-7 ps-0">
                                <input type="text" name="loginEmail" placeholder="person@example.com" required disabled="true" value="" class="w-100 mo-saml-login-text-field">
                            </div>
                        </div>
                        <div class="row align-items-center justify-content-center mt-4 mo-saml-already-reg-field">
                            <div class="col-md-5">
                                <h6 class="text-secondary">Password <span class="text-danger">* </span>:</h6>
                            </div>
                            <div class="col-md-7 ps-0">
                                <input class="w-100 mo-saml-login-text-field" required type="password" name="password" disabled="true" placeholder="Password (Min. length 6)" minlength="6" pattern="^[(\w)*(!@#$.%^&amp;*-_)*]+$" title="Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&amp;*) should be present.">
                            </div>
                        </div>
                        <div class="row mt-4 text-center">
                            <div class="col-md-12">
                                <input type="submit" name="submit" value="Register" class="mo-saml-bs-btn btn-cstm rounded w-176 me-0" id="mo_saml_reg_btn">
                                <input type="submit" name="submit" value="Login" class="mo-saml-bs-btn btn-cstm rounded w-176 me-0" id="mo_saml_reg_login_btn">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                            </div>
                        </div>
                        <div class="text-center">
                            <input type="button" name="mo_saml_goto_login" id="mo_saml_goto_login" value="Already have an account?" class="border-0 text-info mt-2 h6 mo-saml-alredy-have-btn">
                            <input type="button" name="back" value="Sign Up" class="border-0 text-info mt-2 h6 mo-saml-alredy-have-btn" id="mo_saml_reg_back_btn">
                        </div>
                        <div class="text-center text-secondary mt-3 pe-4 ps-4">
                            <h6 class="mt-2 mo-saml-why-reg border rounded p-3">Need Help? Contact us at <a href="mailto:samlsupport@xecurify.com"><u class="text-info">samlsupport@xecurify.com</u></a> and we'll help you set up SSO with your IdP in no time.</h6>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php
}
