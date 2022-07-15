# Generating Links with Mailchimp that Log-in the Users Automatically

Learn how to generate links in Mailchimp that log in the users automatically.

## How it works

You can basically just populate the `wp_collectme_account_tokens` table and add a merge tag with the token in Mailchimp. 
Then you'll be able to generate the links as described in the [README](/README.md). 

However, it is more convenient to let the [Mailchimpservice](https://github.com/grueneschweiz/mailchimpservice) generate 
the merge tags. This way you can generate a different token for each mailing and thus also reuse the entries in the
`wp_collectme_account_tokens` table, even for different causes.

The principle is simple. The Mailchimpservice generates the token with the following function:
`sha256sum( email || validUntilDate || secret )`. The website can so validate the token without any additional
information except for the shared `secret` and the `validUntilDate`. It then uses the email to retrieve the additional
information from the `wp_collectme_account_tokens` table. If no entry matches the email, the token is considered 
invalid.

## Configuring Mailchimp

Add a merge tag field for the `CAUSE`.

## Configuring the Mailchimpservice

Add the following entry to your sync config `/config/example.com.yml`:

```yaml
- crmKey: email1
  mailchimpKey: CAUSE # The merge tag for the cause
  type: token
  valid: '2022-12-31' # Token is valid until... Supported formats: https://www.php.net/manual/en/datetime.formats.php
  secret: v504CnK8BoNsznCSPWMiZtqLDu6oQOPh # any alphanumeric string
  sync: toMailchimp
```

Then perform a full sync.

## Configuring the Website

Add the following filter:

```php
add_filter( 'collectme_account_token', static function ( $token, $email ) {
    $email = strtolower(trim($email));
    $date = '2022-12-31';  // must match the config of the Mailchimpservice
    $secret = 'v504CnK8BoNsznCSPWMiZtqLDu6oQOPh'; // must match the config of the Mailchimpservice

    if (date_create($date) < date_create()) {
        // token expired
        return false;
    }

    $expectedToken = hash_hmac('sha256', $email . $date, $secret);

    if (!hash_equals($expectedToken, $token)) {
        return false;
    }

    try {
        return \Collectme\Model\Entities\AccountToken::getByEmail($email)->token;
    } catch (\Collectme\Exceptions\CollectmeDBException $e) {
        return false;
    }
}, 10, 2 );
```

## Send the mailing

Add the link to your mailing:

```html
<a href="https://example.com/post-with-collectme-shortcode?action=create&email=*|EMAIL|*&token=*|CAUSE|*">Log-in</a>
```

Where `CAUSE` in `*|CAUSE|*` must match your merge tag.