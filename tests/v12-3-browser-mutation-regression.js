'use strict';
/**
 * V12.3 WP-03/WP-09 regression: cryptographic idempotency and no duplicate
 * in-flight mutation. Isolated browser-source test, not a Hostinger claim.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/operations.js'), 'utf8');
let checks = 0;
function check(actual, expected, message) { assert.deepEqual(actual, expected, message); checks++; }
function scenario(cryptoMode, entries = []) {
  const listeners = {};
  const attrs = {};
  const requests = [];
  const status = { textContent: '', attributes: {}, setAttribute(key, value) { this.attributes[key] = value; } };
  const button = { disabled: false };
  const shell = { querySelector(selector) { return selector === '[data-spdb-operation-status]' ? status : null; } };
  const form = {
    getAttribute(key) { return key === 'data-endpoint' ? 'tasks' : attrs[key] || ''; },
    setAttribute(key, value) { attrs[key] = value; },
    removeAttribute(key) { delete attrs[key]; },
    querySelector(selector) { return selector === 'button[type="submit"]' ? button : null; },
    querySelectorAll() { return []; },
    closest() { return shell; },
    addEventListener(event, fn) { listeners[event] = fn; }
  };
  const unresolved = new Promise(() => {});
  function apiFetch(request) { requests.push(request); return unresolved; }
  apiFetch.use = function () {};
  apiFetch.createNonceMiddleware = () => () => {};
  class FakeFormData { forEach(callback) { entries.forEach(([key,value]) => callback(value, key)); } }
  const windowObject = {
    SPDBOperations: { nonce: 'test', strings: {
      working: 'Working', saved: 'Saved', queued: 'Queued', failed: 'Failed',
      confirm: 'Confirm', secureUnavailable: 'Secure browser required'
    } },
    wp: { apiFetch }, FormData: FakeFormData,
    confirm: () => true, location: { reload() {} }, setTimeout() {}
  };
  if (cryptoMode === 'available') {
    windowObject.crypto = { getRandomValues(bytes) { bytes.fill(0xa5); return bytes; } };
  }
  if (cryptoMode === 'throws') {
    windowObject.crypto = { getRandomValues() { throw Error('Browser security unavailable'); } };
  }
  const context = {
    window: windowObject, document: { querySelectorAll() { return [form]; } },
    Uint8Array, String, Array, Number, Math, Promise, console
  };
  vm.runInNewContext(source, context, { filename: 'assets/js/operations.js' });
  assert.equal(typeof listeners.submit, 'function', 'A submission handler must be installed');
  return { submit() { listeners.submit({ preventDefault() {} }); }, requests, status, button, attrs };
}
function main() {
  const missing = scenario('missing');
  missing.submit();
  check(missing.requests.length, 0, 'No request without browser cryptographic randomness');
  check(missing.status.attributes['data-error'], 'true', 'Missing crypto announced as error');
  check(missing.button.disabled, false, 'Blocked operation leaves button retryable');
  const throwing = scenario('throws');
  assert.doesNotThrow(() => throwing.submit(), 'Crypto failure must deny without exception'); checks++;
  check(throwing.requests.length, 0, 'No request if CSPRNG throws');
  check(throwing.status.attributes['data-error'], 'true', 'CSPRNG exception reported');
  const valid = scenario('available');
  valid.submit();
  check(valid.requests.length, 1, 'One request on first submit');
  check(valid.requests[0].headers['Idempotency-Key'], 'spdb_' + 'a5'.repeat(16), 'CSPRNG-derived key');
  check(valid.button.disabled, true, 'Button disabled in-flight');
  valid.submit();
  check(valid.requests.length, 1, 'Repeated submit must not dispatch a second mutation');
  check(valid.attrs['data-spdb-submitting'], 'true', 'In-flight flag survives duplicate');
  const textual = scenario('available', [
    ['title', '0000123'],
    ['description', '9007199254740993'],
    ['audit_reason', '0007']
  ]);
  textual.submit();
  check(textual.requests[0].data.title, '0000123', 'Numeric-looking task titles are user text, not numbers');
  check(textual.requests[0].data.description, '9007199254740993', 'Long numeric text cannot lose JS integer precision');
  check(textual.requests[0].data.audit_reason, '0007', 'An audit reason must preserve textual leading zeros');
  console.log('All ' + checks + ' V12.3 browser mutation security checks passed.');
}
main();
