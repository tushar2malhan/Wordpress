<?php

function mo_saml_display_demo_request(){
    $mo_saml_admin_email = !empty(get_option('mo_saml_admin_email')) ? get_option('mo_saml_admin_email') : get_option('admin_email');
    $license_plans = mo_saml_license_plans::$license_plans;
    $addons = mo_saml_options_addons::$ADDON_TITLE;
    ?>
    <div class="row container-fluid" id="demo-tab-form">
        <div class="col-md-8 mt-4 ms-5">
            <form method="post" action="">
                <?php wp_nonce_field("mo_saml_demo_request_option");?>
                <input type="hidden" name="option" value="mo_saml_demo_request_option"/>

                <div class="p-4 shadow-cstm bg-white rounded">
                    <h4 class="form-head">Request for Demo</h4>

                    <h6 class="text-center bg-cstm p-4 rounded mt-3">Want to try out the paid features before purchasing the
                        license? Just let us know
                        which plan you're interested in and we will setup a demo for you.</h6>
                    <div class="row align-items-top mt-4">
                        <div class="col-md-3">
                            <h6 class="text-secondary">Email </span>:</h6>
                        </div>
                        <div class="col-md-6">
                            <input type="email" name="mo_saml_demo_email" placeholder="We will use this email to setup the demo for you" required value="<?php esc_attr_e($mo_saml_admin_email, 'miniorange-saml-20-single-sign-on'); ?>" class="w-100">
                        </div>
                    </div>
                    <div class="row align-items-top mt-4">
                        <div class="col-md-3">
                            <h6 class="text-secondary">Request a demo for </span>:</h6>
                        </div>
                        <div class="col-md-6">
                            <select name="mo_saml_demo_plan" id="mo_saml_demo_plan" class="w-100" required="" >
                                <option hidden disabled selected value="">--<?php esc_html_e('Select a license plan','miniorange-saml-20-single-sign-on');?>--</option>
                                <?php
                                foreach($license_plans as $key => $value){
                                ?>
                                <option value="<?php esc_attr_e($key,'miniorange-saml-20-single-sign-on');?>"><?php esc_html_e($value,'miniorange-saml-20-single-sign-on');?>
                                    <?php
                                    }
                                    ?>
                            </select>
                        </div>
                    </div>
                    <div class="row align-items-top mt-4">
                        <div class="col-md-3">
                            <h6 class="text-secondary">Description :</h6>
                        </div>
                        <div class="col-md-6">
                            <textarea rows="6" cols="5" name="mo_saml_demo_description" placeholder="Write us about your requirement" class="w-100"></textarea>
                        </div>
                    </div>


                    <h6 class="text-secondary mt-4">Select the Add-ons you are interested in (Optional) :</h6>
                    <?php
                    $column = 0;
                    $column_start = 0;
                    foreach($addons as $key => $value){?>

                        <?php if($column % 3 === 0) {
                            $column_start = $column;?>
                            <div class="row align-items-top mo-saml-opt-add-ons">
                        <?php } ?>
                        <div class="col-md-4">
                            <input type="checkbox" name="<?php esc_attr_e($key, 'miniorange-saml-20-single-sign-on'); ?>" value="true"> <span><?php esc_html_e($value, 'miniorange-saml-20-single-sign-on'); ?></span>
                        </div>
                        <?php if($column === $column_start + 2) {?>
                            </div>
                        <?php } ?>

                        <?php $column++;
                    }
                    ?>
                    <div class="text-center">
                        <input type="submit" class="btn-cstm bg-info rounded mt-4" name="submit" value="Send Request">
                    </div>
                </div>
            </form>
        </div>
        <?php mo_saml_display_support_form(); ?>
    </div>
    <?php
}