

Requires: PHP 7.4+ with mysqli extension, MySQL/MariaDB.

## Setup
1. Import `db.sql` into your database (creates `korean_app` DB, tables, and 30 starter words).
2. Open `config.php` and set DB_HOST / DB_USER / DB_PASS / DB_NAME.
3. Put this whole folder on your PHP server (or run locally: `php -S localhost:8000` from inside the folder).
4. Visit the site → Register → Login → you'll see today's 10 words.

## Files
- `config.php` – DB connection + session helpers (only file you must edit)
- `db.sql` – database schema + starter words
- `index.php` – redirects to login or today's words
- `register.php`, `login.php`, `logout.php` – auth
- `words.php` – today's 10 words, with "I learned this" toggle and progress bar
- `history.php` – every word studied so far
- `includes/header.php`, `includes/footer.php` – shared layout
- `assets/style.css` – styling

## How the daily words work
Each time a logged-in user opens `words.php` on a new calendar day, the
app picks the next `WORDS_PER_DAY` (10, set in config.php) words they have
never been given, from the `words` table, and locks them in for that date.
Add more rows to `words` any time to give users more days of content.

## Library (PDF books and images)
Open **Library** in the top menu. It has two tabs:
- **PDF Books** – upload PDFs, read them inside the app (`read_book.php`), download or delete them.
- **Images** – upload JPG / PNG / GIF / WebP images, view them full size, download or delete them.

Rules: PDF and image files only, max **25 MB per file**, checked in the browser and again on the server.
Each user only ever sees and deletes their own files. Files are saved in `storage/` under random names and
are only served through `file.php`, which checks who is logged in.

Setup notes for the Library:
- Existing database? Run `db_update_library.sql` once (new installs get the tables from `db.sql`).
- PHP's default upload limit is only 2 MB. `.user.ini` (PHP-FPM) and `.htaccess` (Apache mod_php) raise it.
  With the built-in server, start it like this:
  `php -d upload_max_filesize=26M -d post_max_size=30M -S localhost:8000`
- Make sure the `storage/` folder is writable by the web server.
- Built-in `php -S` ignores `.htaccess`, so `storage/` is not protected there (file names are random, so they can't be guessed).
  On Apache the folder is blocked by `storage/.htaccess`.

=======
# software-project

## Team Software Engineering Projects

Collect all your software engineering project files, resources, etc here.

---

## Project Idea: Korean Vocabulary Learning Software

This project aims to build a software application designed for English speakers who want to learn Korean vocabulary in a structured and consistent way.

### Core Features:

- **Daily Learning System:**
  - Users will learn **10 Korean words per day**.
  - Each word will include:
    - Korean text
    - English meaning
    - Pronunciation (optional audio)

- **Progress Tracking:**
  - Track daily learning progress.
  - Show completed words and remaining words.
  - Maintain streaks or consistency metrics.

- **Milestone-Based Testing:**
  - After completing **100 words**, users unlock a **test/quiz**.
  - The test evaluates vocabulary retention.
  - Score and feedback will be provided.

- **User Dashboard:**
  - Displays:
    - Total words learned
    - Current level
    - Test eligibility status

- **Future Enhancements (Optional):**
  - Spaced repetition system (SRS)
  - Flashcards mode
  - Gamification (points, badges)
  - Mobile app version

---

## `AI_LOG.md`

Every time you update your project, please make a note of what you did in the `AI_LOG.md` file, according to the following template.

```markdown
## [Milestone name] — [Date]
**Tool(s) used:**
**What I asked for:**
**What I kept as-is:**
**What I changed or rejected, and why:**
**Something the AI got wrong that I had to catch:**
>>>>>>> fdd8a7a762cce7911c3105c72b7c1f3a98a9985e
