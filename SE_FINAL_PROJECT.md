# 🏥 SilverCare - Software Engineering Final Project 🚀

**Last Updated:** February 2026

## 📖 Project Context
SilverCare is a web application designed to bridge the care gap between elderly patients and their caregivers. Originally developed for a Web Development course, it is now being expanded and refactored for a Software Engineering final project.

**Tech Stack:** Laravel 11, PHP, PostgreSQL, JavaScript, Tailwind CSS.

**Crucial Constraint:** The grading rubric heavily favors frontend UI/UX polish, functional architecture, and clean code over complex hardware integrations. All Voice-to-Text/Microphone features have been scrapped in favor of text-in/text-out AI integrations and major UI refactoring.

---

## 🗺️ Project Roadmap & Master List

### Phase 1: AI Integrations (Text-Based / UI Enhancements)
- [ ] **Smart Health Trend Analyzer:** Fetch 7 days of vital logs and use an AI API to generate a 2-sentence "virtual nurse" summary for the Caregiver/Elderly analytics view.
- [ ] **AI Medication Safety Checker:** Auto-fill the "Instructions" database column with 3 simple safety tips for senior citizens when a caregiver adds a new medication.
- [ ] **Personalized "Morning Briefing":** Generate a daily, dynamic, AI-written welcome message on the Elderly Dashboard based on their specific medication and checklist schedule.
- [ ] **Dynamic Empathy for "Garden of Wellness":** Generate a context-aware praise message when an elderly user completes a checklist task.

### Phase 2: Architecture & Codebase Polish (Refactoring)
- [x] **5. Refactor the Dashboard "God View":** Break down massive Blade files into smaller, reusable Laravel View Components (`<x-vital-card>`, `<x-task-list>`).
- [x] **6. Centralize "Time Window" Logic:** Moved complex date logic into custom accessors on the `MedicationLog` model.
- [] **7. Secure & Dedicated AI Controller:** Created `AiAssistantController.php` to handle external LLM API calls cleanly.

### Phase 3: Infrastructure
- [ ] **8. AI Package Installation:** Integrate `openai-php/laravel` (or Gemini equivalent) to handle API requests cleanly.
