=== WP OAuth Server ( Login with WordPress ) ===
Contributors: cyberlord92,oauth
Tags: WordPress Login, OAuth Provider, OAuth Server, OAuth2, OpenID
Requires at least: 3.0.2
Tested up to: 6.1
Stable tag: 5.0.5
License: MIT/Expat
License URI: https://docs.miniorange.com/mit-license

Single Sign-On using WordPress - Login to your application / sites using your WordPress account. [24/7 Support]

== Description ==

https://youtu.be/c6v-SqRhg8o

WP OAuth Server plugin turns your WordPress site into an OAuth Server. It allows you to login into Rocket Chat, Invision Community, WordPress, Odoo, EasyGenerator, Salesforce, Zapier, Moodle, Service Now, Edunext, Wickr, Freshdesk, FreshWorks, ServiceNow, ShinyProxy, Knack database, Circlo.so, Tribe.so, Tribe, Mobilize, Nextcloud, Church Online, iSpring LMS, Nextcloud, Academy of Mine, BoardEffect, TalentLMS, PowerSchool and any other OAuth 2.0 compliant applications using WordPress credentials.

Basically, the OAuth Server plugin allows users to login into applications that are OAuth 2.0 compliant, using their WordPress login credentials. As it's name suggests, it follows the OAuth 2.0 protocol. Along with that, it also supports OpenID Connect (OIDC), and JWT protocols.

The primary goal of the OAuth Server plugin is to enable Single Sign On so that users do not need to remember username and password for each application.
Once Single Sign On is enabled, users do not need to store sensitive information to login into different applications.

**Discovery URL**
The discovery url / well-known endpoint can be used to get metadata about your Identity Server. It will return information about the OAuth/OpenID endpoints, issuer URL, supported grant types, supported scopes, key material along with claims in the JSON format. These details can be used by the clients to create an OpenID server request. The well known configuration URL is accessible via /.well-known/openid-configuration, in relation to the issuer URL.

**JWT Token Verification**
JWT signing enables the token's recipient to confirm that the token actually received includes all of the information encoded by the issuer in its original, unaltered form.

HS256, a symmetric signature algorithm, indicates that the signature is generated and verified using the same secret key. It is supported in the free version of the plugin.

RS256, an asymmetric signature algorithm is different from a symmetric algorithm in that a pair of private and public keys is used to sign and validate the data respectively instead of a single secret key.

**Why RSA algorithm should be used?**
The use of a public and private key pair makes RS256 more secure in comparison to HS256 where the public key is shared and might be compromised whereas in RS256, even if you do not have the control over your client, your data remains secure as it is signed using a private key. The premium version of the plugin supports the RS256 algorithm.

**Postman collection**
Postman collection JSON is a file that can be used for testing the configuration of OAuth 2.0 flow in the WP OAuth Server plugin without configuring an external OAuth Client by generating the access token and the API call to the resource endpoint subsequently.


= LIST OF POPULAR OAUTH CLIENTS SUPPORTED =
* <a href="https://plugins.miniorange.com/guide-to-configure-rocket-chat-oauth-client" target="_blank"> Rocket.Chat </a>
* <a href="https://plugins.miniorange.com/guide-to-configure-invision-community-oauth-client" target="_blank"> Invision Community (IPB Forum) </a>
* <a href="https://www.miniorange.com/single-sign-on-(sso)-for-odoo" target="_blank"> Odoo </a>
* <a href="https://plugins.miniorange.com/guide-to-setup-single-sign-on-between-two-wordpress-sites" target="_blank"> WordPress </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-circle-using-wordpress-as-oauth-server" target="_blank"> EasyGenerator </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-salesforce-using-wordpress-as-oauth-server" target="_blank"> Salesforce </a>
* <a href="https://plugins.miniorange.com/zapier-integration-with-wordpress-oauth-server" target="_blank"> Zapier  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-moodle-using-wordpress-as-oauth-server" target="_blank"> Moodle  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-open-edx-edunext-using-wordpress-as-oauth-server" target="_blank"> Edunext  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-wickr-using-wordpress-as-oauth-server" target="_blank"> Wickr  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-freshworks-freshdesk-using-wordpress-as-oauth-server" target="_blank"> Freshdesk  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-freshworks-freshdesk-using-wordpress-as-oauth-server" target="_blank"> FreshWorks  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-servicenow-using-wordpress-as-oauth-server" target="_blank"> ServiceNow  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-knack-using-wordpress-as-oauth-server" target="_blank"> Knack database  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-circle-using-wordpress-as-oauth-server" target="_blank"> Circle.so  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-tribe-using-wordpress-as-oauth-server" target="_blank"> Tribe.so  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-mobilize-using-wordpress-as-oauth-server" target="_blank"> Mobilize  </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-nextcloud-using-wordpress-as-oauth-server" target="_blank"> Nextcloud  </a>
* <a href="https://plugins.miniorange.com/step-by-step-guide-for-wordpress-oauth-server" target="_blank"> iSpring LMS </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-church-online-using-wordpress-as-oauth-server" target="_blank"> Church Online </a>
* <a href="https://plugins.miniorange.com/single-sign-on-sso-for-academy-of-mine-using-wordpress-as-oauth-server" target="_blank"> Academy of Mine </a>
* <a href="https://plugins.miniorange.com/step-by-step-guide-for-wordpress-oauth-server" target="_blank"> BoardEffect </a>
 
= WORDPRESS OAUTH / OPENID CONNECT SERVER USE CASES =
* If you want to use your WordPress site as an Identity Server / OAuth Server / OAuth Provider and use WordPress user's login credentials to login into your client site / application then you can use this plugin. You can also decide what kind of User data / attributes you want to send while Single Sign On into your client site / application.
* If you want to login to your Mobile app / Single Page web app (SPA) using your WordPress credentials, then you can use the Authorization code with PKCE flow grant type to achieve your use case.
* Single set of credentials will be used to login to multiple WordPress websites.
* You can access the NGINX resources using NGINX Authentication. Once you login into your client application using WP OAuth Server credentials, you will get JWT. Your client application can further use it for NGINX Authentication. 

= WORDPRESS OAUTH / OPENID CONNECT SERVER FREE VERSION FEATURES =
* Supports Login with WordPress for **Single Client** application
* **Protocol Support:** OAuth 2.0, OpenID Connect (OIDC)
* **Discovery document** / well-known endpoint for automatic configuration
* JWT signing using **HS256** algorithm
* **Postman collection** for testing OAuth 2.0 flow without actually configuring the client application
* **Server Response:** Sends User ID, username, email, first name, last name, display name in the response
* **Grant types Supported:** Authorization Code grant
* **Multi-Site Support:** Use the plugin in WordPress Multisite network environment
* **Master Switch:** Block / unblock OAuth API calls between OAuth Clients and Server
* **Token Length:** Change the access token length
* <a href="https://plugins.miniorange.com/oauth-api-documentation" target="_blank"> OAuth API Documentation </a>
* <a href="https://plugins.miniorange.com/step-by-step-guide-for-wordpress-oauth-server" target="_blank"> Setup guides </a> to configure the plugin with various OAuth Clients (more coming soon)

= WORDPRESS OAUTH / OPENID CONNECT SERVER PREMIUM VERSION FEATURES =
* All FREE version features
* Supports Login with WordPress for **Multiple Client** applications
* **Server Response:** Sends all the profile attributes along with roles, allows to send custom attributes from usermeta table and also customize the attribute names that need to be sent in server response
* **Grant Types Supported:** Authorization Code Grant, Implicit Grant, Password Grant, Client Credentials Grant, Refresh Token Grant, Authorization Code grant with PKCE flow
* **Token Lifetime:** Configure the access token and refresh token expiry time
* **Enforce State Parameter:** Based on client configuration, you can enable or disable state parameter
* **Authorize / Consent prompt:** Enable / disable the consent screen
* **Redirect / Callback URI Validation:** Enable / disable this feature, based on dynamic redirect to a different pages for certain conditions
* **JWT Signing Algorithm:** Supports signing algorithms HSA and RSA
* **Additional endpoints:** Provides Introspection endpoint, OpenID Connect Single logout endpoint

A grant is a method of acquiring an access token. Deciding which grants to implement depends on the type of client the end user will be using, and the experience you want for your users.

= WE SUPPORT FOLLOWING GRANTS: =
* **Authorization code grant** : This code grant is used when there is a need to access the protected resources on behalf of the user on another third party application.
* **Implicit grant** : This grant relies on resource owner and registration of redirect uri. In authorization code grant users need to ask for authorization and access token each time, but here access token is granted for a particular redirect uri provided by a client using a particular browser.
* **Client credential grant** : This grant type heads towards specific clients, where access token is obtained by client by only providing client credentials. This grant type is quite confidential.
* **Resource owner password credentials grant** : This type of grant is used where the resource owner has a trust relationship with the client. Just by using username and password, provided by resource owner authorization and authentication can be achieved.
* **Refresh token grant** : Access tokens obtained in OAuth flow eventually expire. In this grant type client can refresh his or her access token.
* **Authorization code grant with PKCE flow** : This grant type is used for public clients like mobile and native apps, Single Page web apps, where there is a risk of client secret being compromised.

= REST API AUTHENTICATION =
Rest API is very much open to interact. Creating posts, getting information of users and much more is readily available.
It secures unauthorized access to your WordPress sites/pages using our <a href="https://wordpress.org/plugins/wp-rest-api-authentication/" target="_blank"> WordPress REST API Authentication plugin </a>.

== Installation ==

= From your WordPress dashboard =
1. Visit `Plugins > Add New`
2. Search for `OAuth 2.0 server`. Find and Install `OAuth 2.0 server`
3. Activate the plugin from your Plugins page

= From WordPress.org =
1. Download OAuth 2.0 server.
2. Unzip and upload the `miniorange-oauth-login` directory to your `/wp-content/plugins/` directory.
3. Activate miniOrange OAuth from your Plugins page.

== Frequently Asked Questions ==
= I need to customize the plugin or I need support and help? =
Please email us at info@xecurify.com or <a href="http://miniorange.com/contact" target="_blank">Contact us</a>. You can also submit your query from plugin's configuration page.

= I want a demo or trial of the plugin =
For the demo or trial of the plugin, you can submit a request from the **Trials Available** tab in the plugin or directly email us at <a href="mailto:oauthsupport@xecurify.com" target="_blank">oauthsupport@xecurify.com</a>

= The plugin is not working on my localhost =
If you are using the plugin on localhost, please make sure your site is reachable from your OAuth Client side. If not, you can host your WordPress site publicly to make it work.


== Screenshots ==
1. Add OAuth Client
2. Get Client ID and Client Secret
3. Configurations
4. Advanced settings and other Premium features
5. Server Response
6. Endpoints
7. Demo for premium plugin


== Changelog ==

= 5.0.5 =
* Compatibility with WordPress version 6.1

= 5.0.4 =
* Added RSA algo support with common keys
* Authorize endpoint fix
* other bug fixes

= 5.0.3 =
* Fixed issue with gravity form

= 5.0.2 =
* Fixed the client secret migration issue
* readme update

= 5.0.1 =
* Discovery/.well-known/issuer url now supported
* Added support for HS256 algorithm for JWT token verification
* Added postman collection to test configurations
* Client secret is now stored in encrypted format

= 4.0.1 =
* Vulnerability fixes
* Code improvements

= 3.0.4 = 
* Token Post Response header already sent warning fix

= 3.0.3 = 
* Database Query Optimization

= 3.0.2 = 
* CORS issue fix
* Added trial option of the premium
* Licensing page changes

= 3.0.1 = 
* Added compatibility with WP 5.9
* Improved performance of website by setting autoload to false

= 3.0.0 =
* Support for email attribute in the userinfo endpoint
* Link to the OAuth API documention
* Client specific UI improvements

= 2.13.8 =
* Security Fixes

= 2.13.7 =
* UI improvement - Copy button for endpoints and client credentials
* Bug fix for supplied_redirect_uri
* Consent screen on every login

= 2.13.6 =
* permission_callback warning fix


= 2.13.5 =
* minor bug fixes
* added copy button to copy the client credentials and endpoints
* readme update

= 2.13.4 =
* minor UI updates
* added compatibility with WP 5.7

= 2.13.3 =
* minor bug fixes
* fixed compatibility with Brizzy
* added compatibility with WP 5.6

= 2.13.2 =
* minor bug fixes
* fixed issue with deactivation form
* added compatibility with WP 5.5

= 2.13.1 =
* Added compatibility with WordPress v5.5

= 2.13.0 =
* Added UI fixes
* Updated demo plan fixes
* Minor bugfixes and compatibility fixes

= 2.12.4 =
* Licensing tab fix

= 2.12.3 =
* Added fixes for some features
* Added option to disable authorize screen

= 2.12.2 =
* Added Compatibility with WordPress v5.4

= 2.12.0 =
* Performance Improvements

= 2.11.0 =
* Fixed bug where blank scope led to blank screen
* Fixed "Deny" button resulting in clicking "Allow"
* Fixed unaccounted bytes error notice on activation
* Updated plugin licensing
* Minor UI Improvements

= 2.10.0 =
* Added fixes for Loopback Request failure
* Updated Endpoints based on REST API and Authorize Consent Screen
* Minor Bugfixes

= 2.9.1 =
* Fixed migration issue

= 2.9.0 =
* Fixed bug where bearer access_token was not recognized.
* Updated Endpoints

= 2.8.2 =
* Updated Installation Steps

= 2.8.1 =
* Compatibility changes for miniOrange OAuth Single Sign On

= 2.8.0 =
* Updated registration form
* Advertised Introspection Endpoint

= 2.7.0 =
* Added compatibility for WordPress Version 5.2
* Added fixes for OpenID Connect flow
* Added fixes for OTP related issue
* Updated Endpoints
* Added alternative for Sign Up
* Advertised Scope Based Response

= 2.6.1 =
* Fixed conflicts for function generateRandomString()

= 2.6.0 =
* Advertised new features as per new Licensing Plan

= 2.5.6 =
* Added Compatibility for Rocket.chat

= 2.5.5 =
* Fixed OTP related issue

= 2.5.4 =
* Updated Licensing Plan

= 2.5.3 =
* Added Visual Tour fixes

= 2.5.2 =
* Added bugfixes

= 2.5.1 =
* Added missing files

= 2.5.0 =
* New Features
* Major UI Revamp
* Added Feature Tour

= 2.4.0 =
* Compatibility with WordPress 5.1

= 2.3.0 =
* Added Feedback Form and Updated UI

= 2.2.1 =
* Added support for Invision Community and Rocket.chat

= 2.2.0 =
* Updated UI

= 2.1.0 =
* Fixed the PHP7.2 Compatibility issue

= 2.0.3 =
* Changes in the title

= 2.0.2 =
* Added features

= 2.0.1 =
* Added support for multiple client

= 1.0.1 =
* Initial Release

== Upgrade Notice ==
= 2.9.1 =
* Fixed migration issue

= 2.9.0 =
* Fixed bug where bearer access_token was not recognized.
* Updated Endpoints

= 2.8.2 =
* Updated Installation Steps

= 2.8.1 =
* Compatibility changes for miniOrange OAuth Single Sign On

= 2.8.0 =
* Updated registration form
* Advertised Introspection Endpoint

= 2.7.0 =
* Added compatibility for WordPress Version 5.2
* Added fixes for OpenID Connect flow
* Added fixes for OTP related issue
* Updated Endpoints
* Added alternative for Sign Up
* Advertised Scope Based Response

= 2.6.1 =
* Fixed conflicts for function generateRandomString()

= 2.6.0 =
* Advertised new features as per new Licensing Plan

= 2.5.6 =
* Added Compatibility for Rocket.chat

= 2.5.5 =
* Fixed OTP related issue

= 2.5.4 =
* Updated Licensing Plan

= 2.5.3 =
* Added Visual Tour fixes

= 2.5.2 =
* Added bugfixes

= 2.5.1 =
* Added missing files

= 2.5.0 =
* New Features
* Major UI Revamp
* Added Feature Tour

= 2.4.0 =
* Compatibility with WordPress 5.1

= 2.0.3 =
* Changes in the title

= 2.0.2 =
* Added features

= 2.0.1 =
* Added support for multiple client

= 1.0.1 =
* Initial Release
* Initial Release