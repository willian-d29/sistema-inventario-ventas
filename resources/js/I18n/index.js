import common from './common.js';
import navigation from './navigation.js';
import dashboard from './dashboard.js';
import products from './products.js';
import cash from './cash.js';
import expenses from './expenses.js';
import preferences from './preferences.js';
import auth from './auth.js';
import sales from './sales.js';
import pos from './pos.js';
import reports from './reports.js';
import settings from './settings.js';
import admin from './admin.js';
import help from './help.js';

const dictionaries = [common, navigation, dashboard, products, cash, expenses, preferences, auth, sales, pos, reports, settings, admin, help];

function mergeDeep(target, source) {
  for (const [key, value] of Object.entries(source)) {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      target[key] = mergeDeep(target[key] || {}, value);
    } else {
      target[key] = value;
    }
  }

  return target;
}

export const messages = dictionaries.reduce((carry, dictionary) => {
  for (const locale of ['es', 'en']) {
    carry[locale] = mergeDeep(carry[locale] || {}, dictionary[locale] || {});
  }

  return carry;
}, {});

export function translate(locale, key, replacements = {}) {
  const normalizedLocale = ['es', 'en'].includes(locale) ? locale : 'es';
  const fallbackLocale = normalizedLocale === 'es' ? 'en' : 'es';
  const value = key.split('.').reduce((carry, segment) => carry?.[segment], messages[normalizedLocale])
    ?? key.split('.').reduce((carry, segment) => carry?.[segment], messages[fallbackLocale])
    ?? key;

  if (typeof value !== 'string') {
    return key;
  }

  return Object.entries(replacements).reduce((text, [name, replacement]) => {
    return text.replaceAll(`{${name}}`, String(replacement));
  }, value);
}
