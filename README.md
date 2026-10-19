
# Daily Korean – Plain PHP (no framework)

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
