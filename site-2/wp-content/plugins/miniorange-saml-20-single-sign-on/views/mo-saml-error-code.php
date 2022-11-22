<?php
function error_codes()
{
?>
    <div class="bg-main-cstm mo-saml-margin-left pb-5" id="error-codes">
        <div class="row container-fluid">
            <div class="col-md-8 mt-4 ms-4">
                <div class="p-4 shadow-cstm bg-white rounded">

                    <div class="row">
                        <div class="col-md-6">
                            <h4>Error Codes </h4>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="<?php echo esc_url(mo_saml_add_query_arg(array('tab' => 'save'), sanitize_url($_SERVER['REQUEST_URI']))); ?>" class="btn btn-cstm ms-3"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z" />
                                </svg>&nbsp; Back To Plugin Configuration</a>
                        </div>
                    </div>
                    <div class="form-head"></div>
                    <table class="mo-saml-troubleshoot-table">
                        <tr>
                            <td class="title-text mo-saml-text-center"><b>Error Code</b></td>
                            <td class="title-text mo-saml-text-center"><b>Cause</b></td>
                            <td class="title-text mo-saml-text-center"><b>Description</b></td>
                        </tr>
                        <?php
                        foreach (mo_saml_options_enum_error_codes::$error_codes as $key => $value) {
                        ?>
                        <tr id="<?php esc_attr_e($value['code'], 'miniorange-saml-20-single-sign-on'); ?>">
                            <td>
                                <strong><?php esc_html_e($value['code'], 'miniorange-saml-20-single-sign-on'); ?></strong>
                            </td>
                            <td>
                                <?php esc_html_e($value['cause'], 'miniorange-saml-20-single-sign-on'); ?>
                            </td>
                            <td class="mo-saml-content-td">
                                <?php echo wp_kses($value['description'], array('br'=>array(),'u'=>array())); ?>
                                <br>
                                <strong>Fix: </strong>
                                <?php echo wp_kses($value['fix'], array('a'=>array('href'=>array()))); ?>
                            </td>
                          </tr>  
                         <?php
                        }?>
                    </table>
                    <br>
                    <div class="mo-saml-note">
                        <h5 class="mo-saml-text-center">Reach out to us at <a href="mailto:samlsupport@xecurify.com">samlsupport@xecurify.com</a> if you need any assistance.</h5>
                    </div>
                </div>
            </div>
            <?php mo_saml_display_support_form(); ?>
        </div>
    </div>
    <?php
}
    ?>