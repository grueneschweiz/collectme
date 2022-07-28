/* tslint:disable */
/* eslint-disable */
/**
 * Collectme V2
 * API specification for the Collectme V2 WordPress plugin.  **Authentication**  Users may authenticate themselves using the `POST /api/auth` or `GET /app?action=create` endpoints. Authentication  works with the following two cookies:   - `WP_COLLECTME_AUTH`: Persistent login. Cookie contains the persistent session's `uuid` and a secret that    corresponds to the `session_hash`. - `PHPSESSID`: Session login. Cookie contains the PHP session id.  While `WP_COLLECTME_AUTH` keeps the user logged in (for 5 years), the session cookie has a short ttl (default  session duration) but better performance. The session cookie therefore takes precedence over the `WP_COLLECTME_AUTH` cookie. Both cookies must be deleted, to log the user out.  See [/docs/uml/login-flow.puml](/docs/uml/login-flow.puml) for a diagram of the login flow.  All protected functions do also need a nonce for CSRF protection. The nonce must be sent in the  `X-WP-Nonce` header. It can be obtained from the global JS variable `collectme.nonce`, which is set by the  `HtmlController`.  **URLs**  - `/api` is a placeholder for the api prefix. With pretty permalinks, this will be `/wp-json/collectme/v1`. It is   passed to the app via the global JS variable `collectme.apiBaseUrl`. - `/app` is a placeholder for the url of the page or post where the plugin is used (via shortcode). It is passed to   the app via the global JS variable `collectme.appUrl`. 
 *
 * OpenAPI spec version: 1.0.0
 * Contact: admin@gruene.ch
 *
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen.git
 * Do not edit the class manually.
 */
import type { Stat } from './stat';
/**
 * 
 * @export
 * @interface InlineResponse2002
 */
export interface InlineResponse2002 {
    /**
     * 
     * @type {Stat}
     * @memberof InlineResponse2002
     */
    data?: Stat;
}
