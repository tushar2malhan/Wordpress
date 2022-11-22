<?php
	
	class MOOAuth_Server_Admin_RFD {
	
		public static function requestfordemo() {
			self::demo_request();
		}

		public static function demo_request(){
			$democss = "width: 350px; height:35px;";
		?>
		<div class="mo_demo_layout">
			<h3> <?php esc_html_e('Trials Available','miniorange-oauth-server-free'); ?>  </h3>
			<hr>
			<?php esc_html_e('Want to try out the paid features before purchasing the license? Just submit the demo request and we will setup a demo for you.','miniorange-oauth-server-free');?>
			<form method="post" action="">
				<table class="mo_oauth_cloud_demo_outer_table">
				<tr>
						<td>
							<!-- trial/demo -->
							<input type="hidden" name="option" value="mo_oauth_server_demo_request_form" />
							<?php wp_nonce_field('mo_oauth_server_demo_request_form', 'mo_oauth_server_demo_request_field'); ?>
							<table class="mo_oauth_demo_table_layout">
								<!-- First form start -->

								<tr>
									<td><strong>Email id <p style="display:inline;color:red;">*</p>: </strong></td>
								</tr>
								<tr>
									<td><input required type="email" style="<?php echo esc_attr($democss); ?>" name="mo_auto_create_demosite_email" placeholder="We will use this email to setup the demo for you" value="<?php echo esc_attr(get_option("mo_oauth_admin_email")); ?>" /></td>
								</tr>
						
								<tr>
									<td style="padding-top:20px"><strong><?php esc_html_e('Usecase','miniorange-oauth-server-free'); ?><p style="display:inline;color:red;">*</p> : </strong></td>
								</tr>
								<tr>
									<td>
									<textarea type="text" minlength="15" name="mo_auto_create_demosite_usecase" class="mo_oauth_demo_request_usecase" rows="4" placeholder="<?php esc_html_e('Example: Login to other sites using your WordPress credentials','miniorange-oauth-server-free'); ?>" required value=""></textarea>
									</td>
								</tr>

								<tr>
									<td>
										<input type="hidden" name="mo_auto_create_demosite_demo_plan" value="miniorange-oauth-server-enterprise-common@31.4.0">
									</td>
								</tr>
								
							</table>	
						</td>
							
						<td class="mo_oauth_demo_request_veretical_line">
							<!-- vertical line here -->
						</td>

						<td>
							<b>You can test out all the premium plugin features as per your requirements on a demo site.</b>
							<br>
							<br>
							<b>You will receive credentials for a demo site where our premium plugin is installed via the email provided by you.</b>
						</td>
					</tr>
				</table>
				<input type="submit" class="mo_oauth_demo_submit_button" name="submit" value="<?php esc_html_e('Submit Demo Request','miniorange-oauth-server-free'); ?>" class="button button-primary button-large" />
			</form>
		</div>			
	
			<!-- VIDEO DEMO PART -->

				<div class="mo_oauth_video_demo_container">
					<h3><?php esc_html_e('Request for Video Demo','miniorange-oauth-server-free'); ?>  </h3>
					<hr>
					<div style="display:flex">
						<div class="mo_oauth_video_demo_container_form">
							<form method="post" action="">
								<input type="hidden" name="option" value="mo_oauth_server_video_demo_request_form" />
								<?php wp_nonce_field('mo_oauth_server_video_demo_request_form', 'mo_oauth_server_video_demo_request_field'); ?>
								<h2 style="text-align:center;">REQUEST A VIDEO DEMO</h2>
									<div class="mo_oauth_demo_comtainer_form_table" style="color:#3c434a;">
									<div>
										<strong>Email id <p style="display:inline;color:red;">*</p>: </strong><br>
										<input type="text" class="mo_oauth_video_demo_email"placeholder="We will use this email to setup the demo for you" name="mo_oauth_video_demo_email" >
									</div>
									<div class="mo_oauth_video_demo_datetime_container">
										<table>
											<tr>
												<td><strong>Date<p style="display:inline;color:red;">*</p>: </strong></td>	
												<td><strong>Local Time<p style="display:inline;color:red;">*</p>: </strong></td>
											</tr>
											
											<tr>
												<td>
													<input type="date" class="mo_oauth_video_demo_date" name="mo_oauth_video_demo_request_date" placeholder="Enter the date for demo">
												</td>
												<td>
													<input type="time" class="mo_oauth_video_demo_time" placeholder="Enter your time" name="mo_oauth_video_demo_request_time">
													<input type="hidden" name="mo_oauth_video_demo_time_diff" id="mo_oauth_video_demo_time_diff">
												</td>
											</tr>
											<tr>
												<td></td>
												<td style="color:grey;">Eg:- 12:56, 18:30, etc.</td>
											</tr>
										</table>
									</div>
									<div style="margin-top:20px;" >
										<strong>Usecase/ Any comments:<p style="display:inline;color:red;">*</p>: </strong><br>
										<textarea name="mo_oauth_video_demo_request_usecase_text" class="mo_oauth_video_demo_form_usecase" cols="43" rows="10" minlength="15" placeholder="Example: Login to other sites using your WordPress credentials"></textarea>
									</div>
									<input type="submit" class="mo_oauth_video_demo_submit_button" value="Submit" class="mo_oauth_video_demo_usecase">
								</div>
							</form>
						</div>
						<div style="width: 58px">
							<!-- empty div for space -->
						</div>
						<div class="mo_oauth_demo_container_gif_section">
							<div class="mo_oauth_video_demo_message">
								Your overview <a style="color:#012970"><strong>Video Demo</strong></a> will include
							</div>
							<div >
								<img class="mo_oauth_video_demo_gif" src="<?php echo esc_attr(plugin_dir_url( __FILE__ )) .'/img/setup-gif.gif'; ?>" alt="mo-demo-gif">
							</div>
							<div class="mo_oauth_video_demo_bottom_message" >
								<div style="margin-top:25px">
									You can set up a screen share meeting with our developers to walk you through our plugin featuers.
								</div>
								<div class="mo_oauth_video_demo_bottom_message_first">
									<img class="mo_oauth_video_demo_tick_icon" src="<?php echo esc_attr(plugin_dir_url( __FILE__ )) .'/img/check.png'; ?>"  alt="">
									Overview of all Premium Plugin features.
								</div>	
								<div style="margin-top:10px">
									<img class="mo_oauth_vedio_demo_support_icon" src="<?php echo esc_attr(plugin_dir_url( __FILE__ )) .'/img/support.png'; ?>"  alt="">
									Get a guided demo from a Developer via screen share meeting.
								</div>
							</div>
						</div>
					</div>							
				</div>	
			
			<script>
				var d = new Date();
    			var n = d.getTimezoneOffset();
    			document.getElementById("mo_oauth_video_demo_time_diff").value = n;
			</script>	
			
		<?php

		}
	}
