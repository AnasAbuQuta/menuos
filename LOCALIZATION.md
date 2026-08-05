# MenuOS localization

MenuOS uses the existing Vue I18n catalogs in `frontend/src/i18n/locales`. Arabic and English catalogs must keep identical nested keys. Arabic uses RTL and the natural product terminology below; English uses LTR.

## Terminology

- Dashboard: لوحة التحكم
- Menu items: عناصر القائمة
- Categories: التصنيفات
- Public menu: القائمة العامة
- Setup: الإعداد
- Analytics: التحليلات
- Theme: المظهر
- Preview: معاينة
- Save settings: حفظ الإعدادات

Product and technical terms intentionally remain unchanged where clarity requires it: MenuOS, WhatsApp, QR, PWA, URL, SVG, HEX, and API. Restaurant-entered content is never treated as interface text. The Bella Pasta names and Italian dish copy on the landing-page product mockup are intentional demo content.

## Guarding new UI text

`npm run test` runs `frontend/tests/localization.test.js`. It checks catalog parity and catches common hardcoded English text in Vue templates, accessible attributes, and toast/API fallback messages.

Translate a new user-facing string in both catalogs. Add a guard exception only for a narrowly justified product term or intentional demo value, using an exact anchored expression in `allowedVisibleText`; document any new category of exception here.

The static offline page cannot depend on Vue. It reads the same `menuos_locale` key and contains concise Arabic and English copies locally so it remains usable without network access.
