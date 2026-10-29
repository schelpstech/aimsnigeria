# AIMS Nigeria student website

The public website is in `dist/`. It is buildless HTML, CSS and JavaScript.

## Local preview

With AMPPS Apache running, open:

http://localhost/aimsnigeria/

The root `.htaccess` serves the website from `dist/` internally, so visitors do not need `/dist/` in the URL. The existing `/dist/` address also remains available.

## Editing and deployment

- `dist/index.html` contains page content, programme listings, certification stages and forms.
- `dist/styles.css` contains the design system and responsive layouts.
- `dist/site.js` handles navigation, programme filters, course selection, form tabs and submissions.
- Upload the contents of `dist/` to the public website directory, keeping the assets beside `index.html`.
- The existing `.openai/hosting.json` also identifies `dist/` as the static hosting directory.

Application and enquiry forms retain the existing endpoint at `https://aimsnigeria.org.ng/forms/submit.php`. The backend in `cpanel-endpoint/` is unchanged; see its README for configuration. Confirm mail delivery from the deployed origin after release. Local checks do not send real applications or enquiries.

The original seal is retained as `aims-logo.png`; `aims-seal.png` is an optimized header version. The illustrative student photograph is from [Pexels, photo 6147369](https://www.pexels.com/photo/6147369/). It does not depict or identify AIMS participants. Replace `students.jpg` with an approved institute photograph when available.

Plus Jakarta Sans headings and Inter body text are self-hosted in `dist/fonts/`, with system sans-serif fallbacks. Their open-source licenses are included. The palette uses navy, white, slate and restrained gold accents. No package installation or build step is needed.

The pre-redesign page is preserved at `qa/original-index.html` for rollback. Validation notes and desktop/mobile screenshots are also in `qa/`.
