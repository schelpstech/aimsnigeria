# Design verification

Checked on 29 October 2026 through the AMPPS preview.

- JavaScript syntax check passed.
- Desktop, 320px phone, 390px phone and 768px tablet layouts: no horizontal overflow.
- All programme filters: 8 total, 4 technology, 2 information practice, 2 business.
- Selecting Data Analytics selects the same course in the application form.
- Application and enquiry tabs switch; arrow-key navigation works.
- Mobile menu opens, and closes when a navigation link is selected.
- Empty enquiry submission is blocked by required-field validation.
- Policy links expand the relevant disclosure.
- Images load; no browser console errors observed.
- No duplicate IDs, missing internal anchors or unmatched explicit field labels.
- Header seal reduced from 1,792,957 bytes to 48,786 bytes; original retained.

Live submissions and email delivery were not exercised. Existing PHP endpoint files and configuration were unchanged.

## Typography and color refinement

- Replaced Source Serif 4 / Manrope with locally hosted Plus Jakarta Sans / Inter.
- Applied navy, white, slate and gold throughout the interface; neutralized the photo overlay.
- Root URL serves both WOFF2 font assets successfully.
- Checked 320px, 390px, 768px and 1280px layouts. No horizontal overflow after adjusting the contact email size.
- Text contrast: primary 14.31:1, muted on light background 5.38:1, gold emphasis on light background 4.77:1, white button text 13.83:1.
- No browser console errors observed. git diff --check passed.
- Final previews: type-color-desktop.jpg and type-color-mobile.jpg.
