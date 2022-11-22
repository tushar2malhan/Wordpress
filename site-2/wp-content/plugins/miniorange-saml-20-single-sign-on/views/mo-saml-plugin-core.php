<?php

function mo_saml_display_plugin_dependency_warning()
{
    if (! Utilities::mo_saml_is_curl_installed() ) {
?>
        <p><span style="color: #FF0000; ">(Warning: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP
                    cURL extension</a> is not installed or disabled)</span></p>
    <?php
    }

    if (!mo_saml_is_openssl_installed()) {
    ?>
        <p><span style="color: #FF0000; ">(Warning: <a href="http://php.net/manual/en/openssl.installation.php" target="_blank">PHP
                    openssl extension</a> is not installed or disabled)</span></p>
    <?php
    }

    if (!mo_saml_is_dom_installed()) {
    ?>
        <p><span style="color: #FF0000; ">(Warning: PHP
                dom extension is not installed or disabled)</span></p>
    <?php
    }
}

function mo_saml_display_welcome_page()
{
    ?>

    <input type="hidden" value="<?php esc_attr_e(get_option("mo_is_new_user"), 'miniorange-saml-20-single-sign-on'); ?>" id="mo_modal_value">
    <div id="getting-started" class="modal" style="display: none">

        <div class="modal-dialog modal-dialog-centered" role="document">

            <div class="modal-content mt-3">
                <span class="pt-2" style="cursor: pointer" onclick="skip_plugin_tour();"><i class="dashicons dashicons-dismiss float-end"></i></span>
                <div class="modal-header d-block text-center">
                    <h2 class="h1 text-info"><?php esc_html_e('Let\'s get started!', 'miniorange-saml-20-single-sign-on'); ?></h2>
                    <div class="bg-cstm p-3 mt-3 rounded">
                        <p class="h6"><b><?php esc_html_e('Hey, Thank you for installing miniOrange SSO using SAML 2.0 plugin', 'miniorange-saml-20-single-sign-on'); ?></b>.</p>
                        <p class="h6"><?php esc_html_e('We support all SAML 2.0 compliant Identity Providers. ', 'miniorange-saml-20-single-sign-on');

                                        wp_kses(__('Please find some of the well-known <b>IdP configuration guides</b> below.'), array('b' => array()));
	                                    wp_kses(__(' If you do not find your IDP guide here, do not worry! mail us at <a href="mailto:samlsupport@xecurify.com">samlsupport@xecurify.com</a>'), array('a' => array('href'=> array()))); ?> </p>
                        <p class="h6"><?php esc_html_e('Make sure to check out the list of supported', 'miniorange-saml-20-single-sign-on'); ?> <a onclick="skip_plugin_tour();" href="<?php echo esc_url(add_query_arg(array('tab' => 'addons'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"><?php esc_html_e('add-ons', 'miniorange-saml-20-single-sign-on'); ?></a> <?php esc_html_e('to increase the functionality of your WordPress site.', 'miniorange-saml-20-single-sign-on'); ?></p>

                    </div>
                </div>

                <div class="modal-body">
                    <?php
                    $index = 0;
                    foreach (mo_saml_options_plugin_idp::$IDP_GUIDES as $key => $value) {

                        $url_string = 'https://plugins.miniorange.com/' . trim($value[1]);

                        if ($index % 5 === 0) { ?>
                            <div class="idp-guides-btns">
                            <?php } ?>
                            <button class="guide-btn" onclick="window.open('<?php echo esc_url($url_string, 'miniorange-saml-20-single-sign-on'); ?>','_blank')"><img class="idp-guides-logo <?php esc_attr_e($key, 'miniorange-saml-20-single-sign-on'); ?>" src="<?php echo esc_url(Utilities::mo_saml_get_plugin_dir_url() . 'images/idp-guides-logos/' . $value[0] . '.png'); ?>" /><?php esc_html_e($key, 'miniorange-saml-20-single-sign-on'); ?></button>
                        <?php
                        if ($index % 5 === 4) {
                            echo '</div>';
                            $index = -1;
                        }
                        $index++;
                    }

                        ?>
                            </div>

                </div>
                <div class="modal-footer d-block" style="position: sticky;">
                    <button type="button" class="btn-cstm rounded mt-3" id="skip-plugin-tour" onclick="skip_plugin_tour()"><?php esc_html_e('Configure Your IDP Now', 'miniorange-saml-20-single-sign-on'); ?></button>

                </div>
            </div>

        </div>

    </div>
    <script>
        document.onkeydown = function(evt) {
            evt = evt || window.event;
            if (evt.keyCode == 27) {
                skip_plugin_tour();
            }
        };
    </script>


<?php
}

function mo_saml_display_plugin_header($active_tab)
{
?>
    <!-- First Slot (Buttons) -->
    <div class="wrap shadow-cstm p-3 me-0 mt-0 mo-saml-margin-left">
        <?php if ($active_tab == 'licensing' || (isset($_REQUEST['page']) && $_REQUEST['page'] == 'mo_saml_licensing')) { ?>
            <h3 class="text-center"><?php esc_html_e('miniOrange SSO using SAML 2.0', 'miniorange-saml-20-single-sign-on'); ?></h3>
            <div class="float-start"><a class="bg-light text-dark rounded h6 p-2" href="<?php echo esc_url(mo_saml_add_query_arg(array('tab' => 'save'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"> Back To Plugin Configuration</a></div>
            <br>
            <div class="text-center text-danger"><?php esc_html_e('You are currently on the Free version of the plugin', 'miniorange-saml-20-single-sign-on'); ?></div>
        <?php } else {
           
        ?>

            <div class="row align-items-top">
                <div class="col-md-5 h3 ps-4">
                    <?php esc_html_e('miniOrange SSO using SAML 2.0', 'miniorange-saml-20-single-sign-on'); ?>
                </div>
                <div class="col-md-3 text-center">
                    <a id="license_upgrade" class="text-white ps-4 pe-4 pt-2 pb-2 btn-prem prem-btn-cstm" href="<?php echo esc_url(mo_saml_add_query_arg(array('tab' => 'licensing'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"><?php esc_html_e('Premium Plans | Upgrade Now', 'miniorange-saml-20-single-sign-on'); ?></a>
                </div>
                <div class="col-md-4 text-end d-flex align-items-center justify-content-end">
                    <a class="pb-3 pt-3 ps-5 pe-5 pop-up-btns" target="_blank" href="https://forum.miniorange.com/">Forum</a>
                    <a class="me-2 pb-3 pt-3 ps-5 pe-5 pop-up-btns" href="?page=mo_saml_enable_debug_logs&tab=debug-logs">Troubleshoot</a>
                </div>
            </div>

        <?php } ?>

    </div>

<?php
}

function mo_saml_display_plugin_tabs($active_tab)
{
?>
    <div class="bg-main-cstm pb-4 mo-saml-margin-left" id="container">
        <span id="mo-saml-message"></span>

        <?php if ($active_tab != 'licensing' && !(isset($_REQUEST['page']) && $_REQUEST['page'] == 'mo_saml_licensing')) { ?>
            <div id="mo-saml-tabs" class="d-flex text-center pt-3 border-bottom ps-5">
                <a id="sp-setup-tab" class="mo-saml-nav-tab-cstm <?php esc_html_e(($active_tab == 'save' ? 'mo-saml-nav-tab-active' : ''),'miniorange-saml-20-single-sign-on'); ?>" href="<?php echo esc_url(add_query_arg(array('tab' => 'save'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"><?php esc_html_e('Service Provider Setup', 'miniorange-saml-20-single-sign-on'); ?></a>
                <a id="sp-meta-tab" class="mo-saml-nav-tab-cstm <?php esc_html_e(($active_tab == 'config' ? 'mo-saml-nav-tab-active' : ''), 'miniorange-saml-20-single-sign-on'); ?>" href="<?php echo esc_url(add_query_arg(array('tab' => 'config'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"><?php esc_html_e('Service Provider Metadata', 'miniorange-saml-20-single-sign-on'); ?></a>
                <a id="attr-role-tab" class="mo-saml-nav-tab-cstm <?php esc_html_e(($active_tab == 'opt' ? 'mo-saml-nav-tab-active' : ''), 'miniorange-saml-20-single-sign-on'); ?>" href="<?php echo esc_url(add_query_arg(array('tab' => 'opt'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"><?php esc_html_e('Attribute/Role Mapping', 'miniorange-saml-20-single-sign-on'); ?></a>

                <a id="redir-sso-tab" class="mo-saml-nav-tab-cstm <?php esc_html_e(($active_tab == 'general' ? 'mo-saml-nav-tab-active' : ''), 'miniorange-saml-20-single-sign-on'); ?>" href="<?php echo esc_url(add_query_arg(array('tab' => 'general'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"><?php esc_html_e('Redirection & SSO Links', 'miniorange-saml-20-single-sign-on'); ?></a>
                <a id="addon-tab" class="mo-saml-nav-tab-cstm <?php esc_html_e(($active_tab == 'addons' ? 'mo-saml-nav-tab-active' : ''),'miniorange-saml-20-single-sign-on'); ?>" href="<?php echo esc_url(add_query_arg(array('tab' => 'addons'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"><?php esc_html_e('Add-Ons', 'miniorange-saml-20-single-sign-on'); ?></a>
                <a id="demo-tab" class="mo-saml-nav-tab-cstm <?php esc_html_e(($active_tab == 'demo' ? 'mo-saml-nav-tab-active' : ''), 'miniorange-saml-20-single-sign-on'); ?>" href="<?php echo esc_url(add_query_arg(array('tab' => 'demo'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"><?php esc_html_e('Demo Request', 'miniorange-saml-20-single-sign-on'); ?></a>
                <a id="acc-tab" class="mo-saml-nav-tab-cstm <?php esc_html_e(($active_tab == 'account-setup' ? 'mo-saml-nav-tab-active' : ''),'miniorange-saml-20-single-sign-on'); ?>" href="<?php echo esc_url(add_query_arg(array('tab' => 'account-setup'), sanitize_url($_SERVER['REQUEST_URI']))); ?>"><?php esc_html_e('Account Setup', 'miniorange-saml-20-single-sign-on'); ?></a>
            </div>
            <?php
            if ($active_tab == 'save') {
                mo_saml_apps_config_saml();
            } else if ($active_tab == 'opt') {
                mo_saml_save_optional_config();
            } else if ($active_tab == 'config') {
                mo_saml_configuration_steps();
            } else if ($active_tab == 'general') {
                mo_saml_general_login_page();
            } else if ($active_tab == 'addons') {
                mo_saml_show_addons_page();
            } else if ($active_tab == 'demo') {
                mo_saml_display_demo_request();
            } else if ($active_tab == 'account-setup') {
                if (mo_saml_is_customer_registered_saml()) {
                    mo_saml_show_customer_details();
                } else {
                    mo_saml_show_new_registration_page_saml();
                }
            } else {
                mo_saml_apps_config_saml();
            }
            ?>
            <a class="contact-us-cstm d-none"><span class="d-flex justify-content-center align-items-center pt-3 text-white"><svg width="16" height="16" fill="currentColor" class="mt-1" viewBox="0 0 16 16">
                        <path d="M8 1a5 5 0 0 0-5 5v1h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a6 6 0 1 1 12 0v6a2.5 2.5 0 0 1-2.5 2.5H9.366a1 1 0 0 1-.866.5h-1a1 1 0 1 1 0-2h1a1 1 0 0 1 .866.5H11.5A1.5 1.5 0 0 0 13 12h-1a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h1V6a5 5 0 0 0-5-5z" />
                    </svg> &nbsp;&nbsp;miniOrange Support</span></a>

        <?php } else if ($active_tab == 'licensing' ||     (isset($_REQUEST['page']) && $_REQUEST['page'] == 'mo_saml_licensing')) {
            mo_saml_show_licensing_page();
        } ?>
    </div>
<?php
}

function mo_saml_troubleshoot_card()
{ ?>
 <div class="bg-white text-center shadow-cstm rounded contact-form-cstm mt-4 p-4" >
  <div class="mo-saml-call-setup p-3">
    <h6>Facing issues? Check out the Troubleshooting options available in the plugin</h6>
    <hr />
    <div class="row align-items-center mt-3 justify-content-center">
        <a href="?page=mo_saml_enable_debug_logs&tab=debug-logs" class="mo-saml-bs-btn btn-cstm text-white w-50">Troubleshoot</a>
    </div>
 </div>
 </div>
  <?php
}

function mo_saml_display_keep_settings_intact_section()
{
?>
    <div class="bg-white text-center shadow-cstm rounded contact-form-cstm mt-4 p-4" id="mo_saml_keep_configuration_intact">
        <div class="mo-saml-call-setup p-3">
            <h6 class="text-center">Keep configuration Intact</h6>
            <form name="f" method="post" action="" id="settings_intact">
                <?php wp_nonce_field('mo_saml_keep_settings_on_deletion'); ?>
                <input type="hidden" name="option" value="mo_saml_keep_settings_on_deletion" />
                <hr>
                <div class="row align-items-top mt-3">
                    <div class="col-md-9">
                        <h6 class="text-secondary">Enabling this would keep your settings intact when plugin is uninstalled</h6>
                    </div>
                    <div class="col-md-3 ps-0">
                        <input type="checkbox" id="mo-saml-switch-keep-config" name="mo_saml_keep_settings_intact" class="mo-saml-switch" <?php checked(get_option('mo_saml_keep_settings_on_deletion') == 'true'); ?> onchange="document.getElementById('settings_intact').submit();">
                        <label class="mo-saml-switch-label" for="mo-saml-switch-keep-config"></label>
                    </div>
                </div>
            </form>
        </div>
        <blockquote class="mt-3 mb-0">Please enable this option when you are updating to a Premium version</blockquote>
    </div>
<?php
}

function mo_saml_display_suggested_idp_integration()
{
?>
    <div class="mo-saml-card-glass mt-4" id="mo-saml-ads-text">
        <div class="mo-saml-ads-text">
            <h5 class="text-center" id="mo-saml-ads-head">Wait! You have more to explore</h5>
            <hr />
            <ul class="ps-1">
                <p id="mo-saml-ads-cards-text"></p>
                <a target="_blank" href="" class="text-warning" id="ads-text-link">Azure AD / Office 365 Sync</a>
                <a target="_blank" href="" class="text-warning float-end" id="ads-knw-more-link">Azure AD / Office 365 Sync</a>
            </ul>
        </div>
    </div>
    <?php

}

function mo_saml_display_suggested_add_ons()
{
    $suggested_addons = mo_saml_options_suggested_add_ons::$suggested_addons;

    foreach ($suggested_addons as $addon) {
    ?>

        <div class="mo-saml-card-glass mt-4">
            <div class="mo-saml-ads-text">
                <h5 class="text-center"><?php esc_html_e($addon['title'], 'miniorange-saml-20-single-sign-on'); ?></h5>
                <hr />
                <ul class="ps-1">
                    <p><?php esc_html_e($addon['text'], 'miniorange-saml-20-single-sign-on'); ?></p>
                    <a target="_blank" href="<?php echo esc_url($addon['link']); ?>" class="text-warning">Download</a>
                    <a target="_blank" href="<?php echo esc_url($addon['knw-link']); ?>" class="text-warning float-end">Know More</a>
                </ul>
            </div>
        </div>

<?php
    }
}
