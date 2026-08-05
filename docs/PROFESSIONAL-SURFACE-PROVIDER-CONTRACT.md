# File 23 Professional Surface Provider Contract

## Purpose

File 23 provides a private One-Stop Doctor and Founder operational experience without becoming the native owner of appointments, Smail/messages, reviews, followers, downloads, support cases, books, courses or learning records.

A professional surface is shown only through a provider that has already registered a valid File 23 adapter, has a current File 23-owned acceptance record and returns a bounded same-origin destination for the current approved actor.

## Supported surface keys

- `appointments`
- `messages`
- `reviews`
- `followers`
- `downloads`
- `support`
- `learning`

Unknown keys are ignored. File 23 does not invent native routes or fallback destinations.

## Registration and selection

The native module first registers its normal adapter through `spdb/register_adapters`.

A separate selector may nominate that already-registered provider for one surface:

```php
add_filter(
    'spdb_native_professional_surface_provider',
    static function ( string $provider_key, string $surface, int $user_id ): string {
        if ( 'messages' === $surface ) {
            return 'sabri_communication_network';
        }

        return $provider_key;
    },
    10,
    3
);
```

The selector returns only a canonical provider key. It does not return a URL, capability, acceptance state or final contract.

## Adapter method

The selected registered adapter may expose this optional method:

```php
public function get_professional_surface_contract( string $surface, int $user_id ): array;
```

The method returns a privacy-minimized array:

```php
return array(
    'provider_key'     => 'sabri_communication_network',
    'provider_version' => '2.0.0',
    'contract_version' => SPDB_CONTRACT_VERSION,
    'capability'       => 'sn_view_own_messages',
    'url'              => home_url( '/messages/' ),
    'enabled'          => true,
);
```

## Mandatory validation

File 23 rejects the surface unless all conditions pass:

1. File 00 is available and the current actor is approved.
2. The selected provider key is canonical.
3. The provider is present in the canonical request-scoped File 23 adapter registry.
4. The adapter supplies the contract itself; an unrelated plugin cannot inject the final contract.
5. `provider_key` exactly matches the selected registered provider.
6. `provider_version` is valid semantic versioning and exactly matches registered metadata.
7. `contract_version` exactly matches `SPDB_CONTRACT_VERSION`.
8. The File 23-owned acceptance record is valid for the exact provider version, File 23 contract version and File 23 plugin version.
9. Production requires `production_accepted`; a non-production environment may use `staging_accepted` or `production_accepted`.
10. The current actor passes the provider-declared WordPress capability at render time.
11. The destination uses the exact same scheme, host and normalized port as the canonical site.
12. Embedded credentials and URL fragments are rejected.

A missing, stale, revoked, incompatible, throwing or unaccepted provider fails closed and produces no dead navigation item.

## Acceptance ownership

Providers cannot self-approve. The contract contains no `accepted` field. File 23 acceptance is governed by `SPDB_Adapter_Acceptance` and is bound to evidence, Founder authority, current two-factor authentication, provider version, File 23 contract version, File 23 plugin version and environment.

## Privacy and data ownership

The contract must not include patient information, message bodies, identity documents, phone numbers, email addresses, clinical details, diagnosis, prescription, payment data, tokens, secrets or unrestricted object identifiers.

The destination opens the native module. File 23 does not copy, mutate or become source of truth for the native record.

## Conditional modules

A conditional future owner such as CF-02 Support and Appeals must not register an active production surface until its activation and extraction gates are formally approved. Until then, the current approved native support owner may register the surface under its own versioned contract.

## Required provider tests

Every provider implementation must test:

- approved actor allowed and unauthorized actor denied;
- suspended/pending actor denied;
- provider absent, disabled, stale, revoked and throwing states;
- staging versus production acceptance;
- provider and contract version drift;
- external URL, scheme downgrade, alternate port, credentials and fragment rejection;
- no patient/message/clinical/payment secrets in contract or logs;
- native route authorization rechecked again on arrival.

## Release boundary

Source and automated-QA completion do not establish staging or production acceptance. Real-provider installation, Hostinger staging, role matrix, browser/accessibility, cache isolation, rollback and Founder sign-off remain required.
