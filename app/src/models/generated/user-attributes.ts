/* tslint:disable */
/* eslint-disable */
/**
 * Collectme V2
 * API specification for the Collectme V2 WordPress plugin.  **Authentication**  Users may authenticate themselves using the `/users/form-auth` or `/users/link-auth` endpoints. Authentication works with the following two cookies:   - `WP_COLLECTME_AUTH`: Persistent login. Cookie contains the persistent session's `uuid` and a secret that    corresponds to the `session_hash`. - `PHPSESSID`: Session login. Cookie contains the PHP session id.  While `WP_COLLECTME_AUTH` keeps the user logged in (for 5 years), the session cookie has a short ttl (default  session duration) but better performance. The session cookie therefore takes precedence over the `WP_COLLECTME_AUTH` cookie. Both cookies must be deleted, to log the user out.  All protected functions do also need a nonce for CSRF protection. The nonce must be sent in the  `X-WP-Collectme-Nonce` header. It can be obtained from the global JS variable `collectme.nonce`, which is set by the  `HtmlController`. 
 *
 * OpenAPI spec version: 1.0.0
 * Contact: admin@gruene.ch
 *
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen.git
 * Do not edit the class manually.
 */
/**
 * 
 * @export
 * @interface UserAttributes
 */
export interface UserAttributes {
    /**
     * 
     * @type {string}
     * @memberof UserAttributes
     */
    email: string;
    /**
     * 
     * @type {string}
     * @memberof UserAttributes
     */
    firstName: string;
    /**
     * 
     * @type {string}
     * @memberof UserAttributes
     */
    lastName: string;
    /**
     * 
     * @type {string}
     * @memberof UserAttributes
     */
    lang: UserAttributesLangEnum;
    /**
     * 
     * @type {Date}
     * @memberof UserAttributes
     */
    created: Date | null;
    /**
     * 
     * @type {Date}
     * @memberof UserAttributes
     */
    updated: Date | null;
}

/**
    * @export
    * @enum {string}
    */
export enum UserAttributesLangEnum {
    D = 'd',
    F = 'f',
    E = 'e'
}

