# Commerce Pays.cz

Off-site [Pays.cz](https://www.pays.cz) payment gateway for Drupal Commerce.

Fork of [drupal.org commerce_payscz](https://www.drupal.org/project/commerce_payscz) 2.0.0 (2020, last upstream release).

Requires Drupal 10/11 and Commerce 2.40+ or 3.x (older sites: use the previous release).

## Setup

- In Pays admin, set the communication URLs to `/pays/ok`, `/pays/err`, `/pays/confirm`.
- Set the validation password (Pays "Heslo pro API") in `settings.php`, see the gateway form. Without it all callbacks are rejected.

## Changelog

### Unreleased (this fork, changes since upstream 2.0.0)

- Drupal 11 / Commerce 3 support; dropped Drupal 9 and Commerce < 2.40 (order locking API).
- Return page completes checkout only for a signed successful payment (status 3) matching the order total and currency.
- Callbacks are rejected when no validation password is set; saving the gateway form no longer clears it; fixed the `settings.php` example in the form.
- Signature compared with `hash_equals()`; a known Pays payment can't be reassigned to another order.
- Amount is sent including haléře (was truncated to whole koruny).
- `/pays/confirm` tries only Pays gateways, locks the order (no duplicate payments) and responds 202.
- A completed payment is never changed by later callbacks.
- `/pays/err` returns the customer to checkout via Commerce's cancel route.
