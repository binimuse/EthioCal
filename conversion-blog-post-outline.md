# Blog post outline — "Building correct Ethiopian calendar conversion for WordPress"

*Target length: ~1,200–1,800 words. Audience: developers who don't know the Ethiopian calendar. Goal: show that the hard part was correctness, and that you got it right. This is the portfolio centerpiece — the converter is what proves the rest of the work is trustworthy.*

---

## 1. Hook (1 short paragraph)

Open with the surprising fact, not the plugin. Something like: it's a different year in Ethiopia right now, the calendar has 13 months, and the leap year doesn't land where you'd expect. Then: "I built a WordPress plugin for it, and the genuinely hard part wasn't the WordPress code — it was getting the dates right."

## 2. What makes the Ethiopian calendar different (set up the problem)

- 12 months of 30 days + a 13th month, **Pagumé**, of 5 or 6 days.
- ~7–8 years behind the Gregorian calendar (explain *why* briefly — the differing calculation of the Annunciation; keep it one sentence, link out for depth).
- New Year (Meskerem 1) lands on Sept 11, or Sept 12 in some years.
- The day traditionally starts at ~6 AM. (Mention as a teaser if you built the clock; otherwise skip.)

Frame each of these as "an assumption a naive implementation gets wrong."

## 3. The trap: don't compute offsets directly

- Show the tempting-but-wrong approach: "just subtract 7 or 8 years and shift the month." Explain why it breaks — the offset depends on where you are relative to New Year, and the leap cycles don't line up.
- This is the "I almost did it the easy way" moment that makes the post relatable.

## 4. The right approach: convert through Julian Day Numbers

- Explain JDN in one or two sentences: a continuous day count used as a neutral pivot between any two calendars.
- The pattern: Ethiopian → JDN → Gregorian (and back). Each calendar only has to know how to convert to/from JDN.
- Name the **Beyene-Kudlek algorithm** as the established method, and that you used a vetted library (`andegna/calender`) rather than hand-rolling the epoch constants — with a sentence on *why* (magic-number bugs are silent and brutal).

## 5. The leap-year subtlety (the centerpiece)

- State the rule: Julian-style, every 4th year without exception — but the Ethiopian leap year is the one **before** the Gregorian leap year.
- Walk one concrete example through so the reader sees it: `ethYear % 4 == 3` → Pagumé has 6 days.
- This is the single most interesting paragraph in the post. Spend time here.

## 6. Proving it: tests against reference dates

- Show the reference-anchor approach — you don't trust the converter until known date pairs check out.
- Include the actual anchors as a small table:
  - 1 Meskerem 2000 → 12 September 2007 (Ethiopian Millennium)
  - 1 Meskerem 2017 → 11 September 2024
  - 6 Pagumé only exists when `ethYear % 4 == 3`
- Mention the final count (117 passing tests) and that the converter tests came first, before any UI existed. This is the discipline story.

## 7. Wiring it into WordPress (kept short)

- One paragraph: everything — block, shortcode, REST endpoint — calls a single conversion core, so the proven-correct logic has exactly one home.
- Mention the Gutenberg block + date picker as the visible payoff. **Embed the demo GIF here.**

## 8. Close

- Reflect: the lesson is that "just a date converter" hid real domain complexity, and the win was treating correctness as the foundation rather than an afterthought.
- Link to the WordPress.org plugin page and the GitHub repo.

---

### Assets to have ready
- [ ] Demo GIF of the date picker in the editor (section 7)
- [ ] The reference-date table (section 6)
- [ ] Links: WordPress.org plugin page, GitHub repo, Wikipedia "Ethiopian calendar" for readers who want depth

### Tone notes
- You're teaching, not bragging. Let the correctness work speak for itself.
- Keep the WordPress-specific parts short; the calendar logic is the interesting part to a general dev audience.
