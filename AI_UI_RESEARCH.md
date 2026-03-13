# SilverCare AI UI Research and Direction

Date: 2026-03-13
Scope: Companion/chatbot refactor for elderly-facing workflows

## Objective
Build an AI interface that is visually unique, practical for older users, and consistent with the existing SilverCare ambient dashboard style.

## Shortlisted Frameworks and Pattern Sources

### 1) Radix Themes + Primitives
Site: https://www.radix-ui.com/
Why it fits:
- Strong accessibility baseline and keyboard behavior support.
- Works well for overlays, dialogs, and composable card interactions.
- Good if we progressively move from a floating widget to a full side panel.

How to use in this project:
- Keep Laravel Blade + Alpine as core.
- Borrow structural patterns: layered surfaces, semantic spacing, and stateful controls.

### 2) Motion (motion.dev)
Site: https://motion.dev/
Why it fits:
- Production-grade animation patterns (layout animation, stagger, timeline sequencing).
- Better interaction rhythm for assistant states: opening, stream pulses, card reveal.
- Documented focus on smooth performance and transition orchestration.

How to use in this project:
- Current implementation uses CSS keyframes for light footprint.
- If we introduce richer interactions in Vite bundles later, Motion can drive advanced transitions.

### 3) shadcn UI ecosystem reference
Site: https://ui.shadcn.com/
Why it fits:
- Not for direct copy-paste here, but useful for design language ideas.
- Good references for polished card hierarchy and actionable command-like interfaces.

How to use in this project:
- Apply style concepts: hierarchy, spacing, not default chat bubbles.
- Keep implementation in Blade/Tailwind to match existing stack.

## Aesthetic Direction Chosen
Name: Ambient Companion

Visual principles:
- Soft glass surfaces with subtle depth and non-flat background movement.
- Distinct palette themes (Coast, Sunrise, Grove) instead of one generic AI-blue identity.
- Practical typography and larger controls for readability.
- Voice-forward input affordance as a first-class interaction.

Interaction principles:
- Staggered, soft message entrance instead of hard bubble pop-ins.
- Streaming indicator integrated into response cards.
- Suggested prompts as touch-friendly action cards.

Accessibility principles:
- Large tap targets, strong contrast text blocks, and reduced visual clutter.
- Escape key closes panel.
- Overlay prevents accidental interaction behind the assistant.

## Template References to Explore Next
- Health assistant side panel patterns (care-focused, low-density).
- Spatial UI cards used in medicine or wellness apps.
- Voice command card stacks with deterministic quick actions.

## Implementation Status
Completed in this pass:
- Shared component refactor in resources/views/components/ai-chat-widget.blade.php.
- Theme token system with cycle control (Coast, Sunrise, Grove).
- Ambient layered visuals, message card redesign, and stream-line animation.

Next implementation phases:
1. Add a "Reduced Motion" mode toggle tied to prefers-reduced-motion.
2. Add structured AI action blocks (task cards, medication chips) from backend payload markers.
3. Add in-panel one-click actions (open meds page, log vitals, open checklist).
4. Introduce optional voice dictation when browser support is confirmed.
