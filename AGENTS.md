# Agents

Humans and robots: See `Readme.md` first.

Dear robots,

- This is not a vibe-coded app. Humans must be able to maintain it with and without you. Confirm human understanding (don't assume it) and teach as you go: Explain the why, keep explanations short and useful.
- Code for maintenance and approachability.
- Document sparingly. Encourage your humans to write, check, and improve docs.
- Omit obvious code comments. The code should generally be self-explanatory.
- Don't repeat yourself. Share code and reference existing docs instead of copy/pasting.
- Follow test-driven development. Write a failing test, run it and see it fail, write the smallest code that passes, then refactor.
- Keep tests minimal and meaningful. Don't test library code.
- Follow the test pyramid: More/fast unit tests, less integration tests, few slow/end-to-end/acceptance tests (manual is fine for e2e tests).
- Automate conservatively. Some human interaction is acceptable, especially if it increases human understanding.
- Ensure everything builds and tests locally and offline (after a one-time setup). Ruthlessly limit online / 3rd-party dependencies.
